#!/usr/bin/env bash
# Functional checks against the dev site from dev/setup.sh.
# Prints one line per check and exits non-zero if any check fails.
set -uo pipefail
dev="$(cd "$(dirname "$0")" && pwd)"
site=https://localhost:8443
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
fails=0

pass() { echo "PASS  $*"; }
fail() { echo "FAIL  $*"; fails=$((fails + 1)); }
check() { # check <description> <expected> <actual>
  if [ "$2" = "$3" ]; then pass "$1 ($3)"; else fail "$1: expected '$2', got '$3'"; fi
}
wp() { "$dev/wp.sh" "$@"; }
json() { python3 -c "import json,sys; d=json.load(sys.stdin); print(eval(sys.argv[1]))" "$1"; }

login() { # login <user> <password>: cookie jar in $tmp/<user>.jar
  curl -sk -c "$tmp/$1.jar" -b "$tmp/$1.jar" -o /dev/null "$site/wp-login.php"
  curl -sk -c "$tmp/$1.jar" -b "$tmp/$1.jar" -o /dev/null -w '%{http_code}' \
    --data-urlencode "log=$1" --data-urlencode "pwd=$2" -d 'testcookie=1&redirect_to=/wp-admin/' "$site/wp-login.php"
}
tab_page() { curl -sk -b "$tmp/$1.jar" "$site/wp-admin/admin.php?page=wc-settings&tab=anythingmcp"; }
create_key() { # create_key <user> <nonce> <permissions>: prints "<http code> <body>"
  curl -sk -b "$tmp/$1.jar" -w ' %{http_code}' \
    -d "action=amcp_wc_create_key&nonce=$2&permissions=$3" "$site/wp-admin/admin-ajax.php"
}
product_post() { # product_post <ck> <cs>: HTTP status of a create-product request
  curl -sk -o "$tmp/post.json" -w '%{http_code}' -u "$1:$2" -H 'Content-Type: application/json' \
    -d '{"name":"AnythingMCP test product","regular_price":"9.90"}' "$site/wp-json/wc/v3/products"
}

wp eval 'file_exists( WP_CONTENT_DIR . "/debug.log" ) && unlink( WP_CONTENT_DIR . "/debug.log" );' >/dev/null
wp option delete amcp_wc_anythingmcp_url >/dev/null 2>&1

# --- Admin: the tab and the setup link ------------------------------------
check "admin login" 302 "$(login admin admin)"
tab_page admin > "$tmp/tab.html"
grep -q 'nav-tab-active">AI Assistants<' "$tmp/tab.html" && pass "AI Assistants tab is shown" || fail "AI Assistants tab missing"
nonce=$(grep -o "amcpWcAdmin = {[^;]*" "$tmp/tab.html" | sed -n 's/.*"nonce":"\([0-9a-f]*\)".*/\1/p')
[ -n "$nonce" ] && pass "page carries an AJAX nonce" || fail "no AJAX nonce on the page"
store_url=$(wp eval 'echo untrailingslashit( home_url() );')
expected="https://cloud.anythingmcp.com/connectors/setup/woocommerce?prefill.WOOCOMMERCE_URL=$(python3 -c 'import sys,urllib.parse; print(urllib.parse.quote(sys.argv[1], safe=""))' "$store_url")"
href=$(sed -n 's/.*id="amcp-wc-open" href="\([^"]*\)".*/\1/p' "$tmp/tab.html" | sed 's/&#038;/\&/g; s/&amp;/\&/g')
check "Open AnythingMCP link" "$expected" "$href"
grep -q 'consumer_secret\|cs_[0-9a-f]\{40\}' <<<"$href" && fail "link contains a secret" || pass "link contains no key or secret"

# --- Read key -------------------------------------------------------------
out=$(create_key admin "$nonce" read)
check "create Read key: HTTP status" 200 "${out##* }"
body=${out% *}
ck=$(json 'd["data"]["consumer_key"]' <<<"$body")
cs=$(json 'd["data"]["consumer_secret"]' <<<"$body")
desc=$(json 'd["data"]["description"]' <<<"$body")
[[ $ck =~ ^ck_[0-9a-f]{40}$ ]] && pass "consumer key format ck_ + 40 hex" || fail "consumer key format: $ck"
[[ $cs =~ ^cs_[0-9a-f]{40}$ ]] && pass "consumer secret format cs_ + 40 hex" || fail "consumer secret format"
[[ $desc =~ ^AnythingMCP\ \([0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}:[0-9]{2}\)$ ]] && pass "description '$desc'" || fail "description: $desc"

row=$(wp db query "SELECT permissions, truncated_key, user_id FROM wp_woocommerce_api_keys WHERE consumer_key = '$(wp eval "echo wc_api_hash( '$ck' );")' AND consumer_secret = '$cs'" --skip-column-names)
check "stored row: permissions, truncated key, owner" "read	${ck: -7}	1" "$row"
check "plain consumer key not in the keys table" 0 "$(wp db query "SELECT COUNT(*) FROM wp_woocommerce_api_keys WHERE consumer_key = '${ck:-NO_KEY}'" --skip-column-names)"
check "plain consumer key not in wp_options" 0 "$(wp db query "SELECT COUNT(*) FROM wp_options WHERE option_value LIKE '%${ck:-NO_KEY}%'" --skip-column-names)"

check "GET products with Read key (basic auth)" 200 "$(curl -sk -o /dev/null -w '%{http_code}' -u "$ck:$cs" "$site/wp-json/wc/v3/products")"
check "GET orders with Read key (query auth, as AnythingMCP)" 200 "$(curl -sk -o /dev/null -w '%{http_code}' "$site/wp-json/wc/v3/orders?consumer_key=$ck&consumer_secret=$cs")"
status=$(product_post "$ck" "$cs")
check "POST product with Read key is refused" 401 "$status"
check "refusal reason" woocommerce_rest_authentication_error "$(json 'd["code"]' <"$tmp/post.json")"

# --- Read/Write key ---------------------------------------------------------
out=$(create_key admin "$nonce" read_write)
check "create Read/Write key: HTTP status" 200 "${out##* }"
body=${out% *}
ck2=$(json 'd["data"]["consumer_key"]' <<<"$body")
cs2=$(json 'd["data"]["consumer_secret"]' <<<"$body")
check "Read/Write key stored as read_write" read_write "$(wp db query "SELECT permissions FROM wp_woocommerce_api_keys WHERE truncated_key = '${ck2: -7}'" --skip-column-names)"
status=$(product_post "$ck2" "$cs2")
check "POST product with Read/Write key succeeds" 201 "$status"
product_id=$(json 'd["id"]' <"$tmp/post.json")
[ -n "$product_id" ] && wp post delete "$product_id" --force >/dev/null

# --- Unknown permission falls back to read ----------------------------------
out=$(create_key admin "$nonce" admin_everything)
ck3=$(json 'd["data"]["consumer_key"]' <<<"${out% *}")
check "unknown permission value is stored as read" read "$(wp db query "SELECT permissions FROM wp_woocommerce_api_keys WHERE truncated_key = '${ck3: -7}'" --skip-column-names)"

# --- Who may create keys ----------------------------------------------------
check "AJAX without nonce is refused" "-1 403" "$(create_key admin '' read)"
check "AJAX as GET is refused" 405 "$(curl -sk -b "$tmp/admin.jar" -o /dev/null -w '%{http_code}' "$site/wp-admin/admin-ajax.php?action=amcp_wc_create_key&nonce=$nonce&permissions=read")"
check "subscriber login" 302 "$(login subscriber subscriber)"
check "subscriber with the admin's nonce is refused" "-1 403" "$(create_key subscriber "$nonce" read)"
code=$(curl -sk -b "$tmp/subscriber.jar" -o /dev/null -w '%{http_code}' "$site/wp-admin/admin.php?page=wc-settings&tab=anythingmcp")
check "subscriber cannot open the tab" 403 "$code"
# A valid nonce of the subscriber's own: the capability check must still refuse.
handler='
	add_filter( "wp_doing_ajax", "__return_true" );
	add_filter( "wp_die_ajax_handler", function () { return function () { throw new Exception( "die" ); }; } );
	$_POST["nonce"] = $_REQUEST["nonce"] = wp_create_nonce( "amcp_wc_create_key" );
	$_POST["permissions"] = "read";
	$_SERVER["REQUEST_METHOD"] = "POST";
	$before = (int) $GLOBALS["wpdb"]->get_var( "SELECT COUNT(*) FROM {$GLOBALS["wpdb"]->prefix}woocommerce_api_keys" );
	ob_start();
	try { amcp_wc_ajax_create_key(); } catch ( Exception $e ) {}
	$out = ob_get_clean();
	$after = (int) $GLOBALS["wpdb"]->get_var( "SELECT COUNT(*) FROM {$GLOBALS["wpdb"]->prefix}woocommerce_api_keys" );
	echo http_response_code() . " " . ( $after - $before ) . " " . $out;
'
out=$(wp eval "$handler" --user=subscriber 2>/dev/null)
check "subscriber with own valid nonce: status, keys created" "403 0" "$(cut -d' ' -f1,2 <<<"$out")"
out=$(wp eval "$handler" --user=shopmanager 2>/dev/null)
check "shop manager (has manage_woocommerce) may create a key: keys created" 1 "$(cut -d' ' -f2 <<<"$out")"

# --- Self-hosted AnythingMCP URL --------------------------------------------
save_url() { # save_url <value>: posts the WooCommerce settings form
  local page settings_nonce
  page=$(tab_page admin)
  settings_nonce=$(sed -n 's/.*name="_wpnonce" value="\([0-9a-f]*\)".*/\1/p' <<<"$page" | head -1)
  curl -sk -b "$tmp/admin.jar" -o "$tmp/save.html" \
    --data-urlencode "amcp_wc_anythingmcp_url=$1" -d "save=Save+changes&_wpnonce=$settings_nonce&_wp_http_referer=/wp-admin/admin.php?page=wc-settings%26tab=anythingmcp" \
    "$site/wp-admin/admin.php?page=wc-settings&tab=anythingmcp"
}
save_url "https://mcp.example.com/"
check "self-hosted URL saved without trailing slash" "https://mcp.example.com" "$(wp option get amcp_wc_anythingmcp_url)"
href=$(tab_page admin | sed -n 's/.*id="amcp-wc-open" href="\([^"]*\)".*/\1/p')
check "link uses the self-hosted URL" "https://mcp.example.com/connectors/setup/woocommerce?prefill.WOOCOMMERCE_URL=${expected#*=}" "$href"
save_url "http://insecure.example.com"
check "http:// address is rejected, previous value kept" "https://mcp.example.com" "$(wp option get amcp_wc_anythingmcp_url)"
grep -q 'must be an https:// address' "$tmp/save.html" && pass "rejection message shown" || fail "no rejection message"
save_url "javascript:alert(1)"
check "javascript: address is rejected" "https://mcp.example.com" "$(wp option get amcp_wc_anythingmcp_url)"
save_url ""
check "empty value goes back to AnythingMCP Cloud" "" "$(wp option get amcp_wc_anythingmcp_url)"

# --- Uninstall --------------------------------------------------------------
wp option update amcp_wc_anythingmcp_url https://mcp.example.com >/dev/null
keys_before=$(wp db query "SELECT COUNT(*) FROM wp_woocommerce_api_keys" --skip-column-names)
wp eval 'define( "WP_UNINSTALL_PLUGIN", "anythingmcp-for-woocommerce/anythingmcp-for-woocommerce.php" ); include WP_PLUGIN_DIR . "/anythingmcp-for-woocommerce/uninstall.php";' >/dev/null
check "uninstall removes the option" missing "$(wp option get amcp_wc_anythingmcp_url >/dev/null 2>&1 && echo present || echo missing)"
check "uninstall keeps the API keys" "$keys_before" "$(wp db query "SELECT COUNT(*) FROM wp_woocommerce_api_keys" --skip-column-names)"

# --- Debug log --------------------------------------------------------------
log=$(wp eval 'echo file_exists( WP_CONTENT_DIR . "/debug.log" ) ? file_get_contents( WP_CONTENT_DIR . "/debug.log" ) : "";')
if [ -z "$log" ]; then pass "debug.log is empty (WP_DEBUG on)"; else fail "debug.log:"; echo "$log"; fi
grep -q "${ck:-none}" <<<"$log" && fail "consumer key in debug.log" || pass "consumer key not in debug.log"

echo
[ "$fails" -eq 0 ] && echo "All checks passed." || echo "$fails check(s) failed."
exit "$fails"
