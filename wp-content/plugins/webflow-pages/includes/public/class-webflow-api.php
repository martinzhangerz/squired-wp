<?php

// Security Check
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

if (!class_exists('Webflow_API')) {


    class Webflow_API
    {
        /**
         * The unique instance of the plugin.
         *
         * @var Webflow_API
         */
        private static $instance;

        private $token;

        private $domain;

        private $site;

        /**
         * Gets an instance of our plugin.
         *
         * @return Webflow_API
         */
        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function init()
        {
            // hooks ajax actions
            if (is_admin()) {
                add_action('admin_init', array($this, 'init_ajax_actions'));
            }
        }

        /**
         * Inits all ajax call needed from frontend
         */
        public function init_ajax_actions()
        {
            add_action("wp_ajax_save_wf_token", array($this, "save_wf_token"));
            add_action("wp_ajax_remove_wf_token", array($this, "remove_wf_token"));
            add_action("wp_ajax_get_wf_site_data", array($this, "get_wf_site_data"));
            add_action("wp_ajax_save_wf_static_rules", array($this, "save_wf_static_rules"));
            add_action("wp_ajax_save_wf_dynamic_rules", array($this, "save_wf_dynamic_rules"));
            add_action("wp_ajax_invalidate_wf_cache", array($this, "invalidate_wf_cache"));
            add_action("wp_ajax_preload_wf_cache", array($this, "preload_wf_cache"));
            add_action("wp_ajax_change_wf_cache_duration", array($this, "change_wf_cache_duration"));
        }


        /**
         * Returns site data from ajax call
         */
        public function get_wf_site_data()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            $site_data = $this->get_main_site_data();

            if (is_wp_error($site_data)) {
                wp_send_json_error($site_data);
            } else {
                wp_send_json_success($site_data);
                wp_die();
            }
        }

        /**
         * Invalidates Cache
         */
        public function invalidate_wf_cache()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            wf_pages_invalidate_cache();
            wp_send_json_success();
            wp_die();
        }

        /**
         * Change WF Cache Duration from ajax call
         */
        public function change_wf_cache_duration()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            if (!isset($_POST['duration'])) {
                wp_send_json_error(new WP_Error("missing_data", "missing duration"));
                wp_die();
            }

            $duration = intval($_POST['duration']);

            wf_pages_set_cache_duration($duration);
            wp_send_json_success();
            wp_die();
        }

        /**
         * Preloads Cache of pages from ajax
         */
        public function preload_wf_cache()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            $cached_page = $this->preload_static_pages_cache();

            if (is_wp_error($cached_page)) {
                wp_send_json_error($cached_page);
                wp_die();
            } else {
                wp_send_json_success($cached_page);
                wp_die();
            }

        }

        /**
         * Preloads static pages cache
         */
        public function preload_static_pages_cache()
        {

            $domain = wf_pages_get_site_domain();
            if ("" == $domain) {
                return new WP_Error("invalid_domain", "Cannot prefetch cache with invalid domain");
            }

            $static = wf_pages_get_static_page_rules();

            $cached = 0;

            foreach ($static as $wp => $wf) {
                $url = untrailingslashit($domain) . esc_url_raw($wf);
                $res = wf_pages_get_url_content($url);
                if (!is_wp_error($res)) {
                    $cached++;
                } else {
                    return $res;
                }
            }

            return $cached;
        }

        /**
         * Returns data attached to the only site you can get with the API Token
         *
         * @return array|mixed|object|string|WP_Error
         */
        public function get_main_site_data()
        {

            $cached_site_data = get_transient("_wf_site_data");

            if ($cached_site_data) {
                return $cached_site_data;
            }

            $site = $this->get_site();

            if (is_wp_error($site)) {
                return $site;
            }

            if (!$site) {
            	return new WP_Error('invalid_site_data', "Site data seems not valid");
            }
            //if (!$site->lastPublished) { Last published is null on site hosted only on subdomains

            // return new WP_Error('site_not_published', __("The API key you used is invalid: your site is not published", WEBFLOW_PAGES_TEXT_DOMAIN));
            //}

            $site_data = $this->get_site_data($site);


            return $site_data;

        }

        /**
         * Saves static rules from Ajax Call
         */
        public function save_wf_static_rules()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            if (!isset($_POST['rules'])) {
                wp_send_json_error(new WP_Error("invalid_data", "Missing rules data"));
                wp_die();
            }

            $rules = json_decode(stripslashes(sanitize_text_field($_POST['rules'])));

            $to_save = array();

            foreach ($rules as $rule_array) {
                if (count($rule_array) != 2) {
                    continue;
                }
                $wp = esc_url_raw($rule_array[0]);
                $wf = esc_url_raw($rule_array[1]);

                $wp = ltrim($wp, "/"); // removes / at start
                $wp = rtrim($wp, "/"); // removes / at end

                $to_save[$wp] = $wf;
            }

            $this->save_static_page_rules($to_save);

            $rules_to_send = array();
            foreach ($to_save as $wp => $wf) {
                $wp = "/$wp"; // adds back the slash
                $rules_to_send[] = [$wp, $wf];
            }

            // Sends back saved rules
            wp_send_json_success($rules_to_send);

            wp_die();

        }

        /**
         * Saves static rules from Ajax Call
         */
        public function save_wf_dynamic_rules()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            if (!isset($_POST['rules'])) {
                wp_send_json_error(new WP_Error("invalid_data", "Missing rules data"));
                wp_die();
            }

            $rules = json_decode(stripslashes(sanitize_text_field($_POST['rules'])));

            $to_save = array();

            foreach ($rules as $rule_array) {
                if (count($rule_array) != 2) {
                    continue; // skip invalid rules format
                }
                $wp = esc_url_raw($rule_array[0]);
                $wf = esc_url_raw($rule_array[1]);

                $wp = ltrim($wp, "/"); // removes / at start
                $wp = rtrim($wp, "*"); // removes * at end
                $wp = rtrim($wp, "/"); // removes / at end

                $to_save[$wp . "/"] = $wf;
            }

            $this->save_dynamic_page_rules($to_save);

            $rules_to_send = array();
            foreach ($to_save as $wp => $wf) {
                $wp = "/$wp*"; // adds back the correct format
                $rules_to_send[] = [$wp, $wf];
            }

            // Sends back saved rules
            wp_send_json_success($rules_to_send);

            wp_die();

        }

        /**
         * Saves static page rules
         *
         * @param $rules
         *
         * @return bool
         */
        public function save_static_page_rules($rules)
        {
            return update_option("_wf_static_page_rules", $rules);
        }

        /**
         * Saves dynamic page rules
         *
         * @param $rules
         *
         * @return bool
         */
        public function save_dynamic_page_rules($rules)
        {
            return update_option("_wf_dynamic_page_rules", $rules);
        }

        /**
         * Removes Wf token from the db and all the site associated data
         *
         *
         */
        public function remove_wf_token()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }
            $this->remove_api_token();
            wp_send_json_success();
            wp_die();
        }

        /**
         * Saves wf api token
         */
        public function save_wf_token()
        {
            if (empty($_POST) || !wp_verify_nonce($_POST['security'], "_wf_ajax")) {
                wp_send_json_error(new WP_Error("wp_nonce_verify", "failed security check"));
                wp_die();
            }

            if (!isset($_POST['token'])) {
                wp_send_json_error(new WP_Error("missing_data", "missing token"));
                wp_die();
            }

            $token = sanitize_text_field($_POST['token']);

            $this->set_api_token($token);
            wp_send_json_success();
            wp_die();
        }

        /**
         * Gets Site data associated: [site, pages, collections]
         *
         * @param $site
         *
         * @return array|mixed|object|string|WP_Error
         */
        private function get_site_data($site)
        {

            if (is_wp_error($site) || !$site) {
                delete_transient("_wf_site_data");
                return $site;
            }
            $pages = $this->get_static_pages($site);

            if (is_wp_error($pages)) {
                delete_transient("_wf_site_data");

                return $pages;
            }
            $collections = $this->list_collections($site->_id);
            if (is_wp_error($collections)) {
                delete_transient("_wf_site_data");

                return $collections;
            }

            $site_domain = $this->get_site_domain($site);
            if (is_wp_error($site_domain)) {
                delete_transient("_wf_site_data");

                return $site_domain;
            }
            $site->domain = "https://$site_domain";

            $site_data = array(
                "site" => $site,
                "pages" => $pages,
                "collections" => $collections
            );

            set_transient("_wf_site_data", $site_data, 60);

            return $site_data;
        }

        /**
         * Returns data needed for the localize script function
         * @return array
         */
        public function get_ajax_data()
        {

            $site_data = $this->get_main_site_data();

            if (is_wp_error($site_data)) {

                $data = array(
                    "hasToken" => $this->has_token() ? "true" : "false",
                    "nonce" => wp_create_nonce("_wf_ajax"),
                );
                if ($this->has_token()) {
                    $this->remove_api_token();
                    // Token with invalid site data
                    $data["error"] = __("Token removed, due to invalid site data", WEBFLOW_PAGES_TEXT_DOMAIN);
                }

                return $data;
            } else {

                $static_rules = wf_pages_get_static_page_rules();

                // frontend requires a / instead of "" for home page format of the rules
                $static_rules_to_send = array();

                foreach ($static_rules as $wp => $wf) {
                    if ("" == $wp || "/" != $wp[0]) { // recovers db errors
                        $wp = "/$wp";
                    }
                    $static_rules_to_send[] = [$wp, $wf];
                }

                $dynamic_rules = wf_pages_get_dynamic_page_rules();

                // frontend requires a / instead of "" for home page format of the rules
                $dynamic_rules_to_send = array();

                foreach ($dynamic_rules as $wp => $wf) {
                    if ("" == $wp || "/" != $wp[0]) { // recovers db errors
                        $wp = "/$wp*";
                    }
                    $dynamic_rules_to_send[] = [$wp, $wf];
                }

                return array(
                    "hasToken" => $this->has_token() ? "true" : "false",
                    "nonce" => wp_create_nonce("_wf_ajax"),
                    "pages" => $site_data['pages'],
                    "collections" => $site_data['collections'],
                    "site" => $site_data['site'],
                    "staticRules" => $static_rules_to_send,
                    "dynamicRules" => $dynamic_rules_to_send,
                    "cacheDuration" => wf_pages_get_cache_duration(),
                );
            }

        }

        /**
         * Returns true if user has a token saved on db
         *
         * @return bool
         */
        public function has_token()
        {
            return $this->get_api_token() != "";
        }

        /**
         * Saves Api Token on db
         *
         * @param $token
         */
        private function set_api_token($token)
        {
            update_option('_wf_api_token', $token);
        }

        /**
         * Removes Api Token
         */
        private function remove_api_token()
        {
            delete_option('_wf_api_token');
            $this->token = null;
            wf_pages_invalidate_cache();
            wf_pages_delete_rules();
        }

        /**
         * Gets Webflow Api Token from db
         *
         * @return string
         */
        private function get_api_token()
        {
            if ($this->token) {
                return $this->token;
            } else {
                $token = get_option('_wf_api_token');

                if ($token) {
                    $this->token = $token;

                    return $token;
                } else {
                    return "";
                }
            }
        }

        /**
         * Returns an array of static pages
         *
         * @param $site
         *
         * @return array|WP_Error
         */
        public function get_static_pages($site)
        {

            $domain_response = $this->get_site_domain($site);

            if (!is_wp_error($domain_response)) {
                $res = wp_remote_get("https://" . trailingslashit($domain_response) . "static-manifest.json");
                return $this->handle_remote_response($res, new WP_Error('static_pages', __("The API key failed. Try publishing your Webflow site first", WEBFLOW_PAGES_TEXT_DOMAIN)));
            } else {
                return $domain_response;
            }
        }


        /**
         * Returns authorization headers needed for api call
         * @return array
         */
        private function get_webflow_headers()
        {

            $token = $this->get_api_token();

            return array(
                "Authorization" => "Bearer $token",
                "accept-version" => "1.0.0",
                "Content-Type" => "application/json; charset=utf-8",
                "user-agent" => "WordPress-Webflow Plugin " . WEBFLOW_PAGES_PLUGIN_VERSION //user-agent must be in lower case to be accepted by the WordPress core apis
            );

        }

        /**
         * Returns the first domain associated with the site or WP_Error
         *
         * @param $site
         *
         * @return string|WP_Error
         */
        public function get_site_domain($site)
        {

            if ($this->domain) {
                return $this->domain;
            }

            $domains_response = $this->get_site_domains($site->_id);
            if (!is_wp_error($domains_response)) {
                if (count($domains_response) > 0) {
                    $this->domain = $domains_response[0]->name;

                    return $this->domain;
                } else {
                    $this->domain = $site->shortName . ".webflow.io";

                    return $this->domain;
                }
            } else {
                return $domains_response;
            }

        }

        /**
         * With API token you can get only the site for that token
         *
         * @return array|mixed|WP_Error
         */
        public function get_site()
        {

            if ($this->site) {
                return $this->site;
            }

            $list_response = $this->list_sites(); // With single token api you get only 1 site

            if (!is_wp_error($list_response) && !empty($list_response)) {
                $this->site = $list_response[0];

                return $this->site;
            } else {
                return $list_response;
            }
        }


        /**
         * Lists Webflow Sites
         *
         * returns an array of Object made as {_id: string, createdOn: Date, name: string, shortName: string, lastPublished: string, previewUrl: string}
         *
         * @return array|WP_Error
         */
        public function list_sites()
        {

            $response = wp_remote_get("https://api.webflow.com/sites", array(
                "headers" => $this->get_webflow_headers(),
            ));

            return $this->handle_remote_response($response, new WP_Error('list_sites', __("The API key you used is invalid: failed to list sites", WEBFLOW_PAGES_TEXT_DOMAIN)));
        }

        /**
         * Gets all webhooks registered on a website
         *
         * @param $site_id
         *
         * @return array|mixed|object|WP_Error
         */
        public function list_webhooks($site_id)
        {
            $response = wp_remote_get(esc_url_raw("https://api.webflow.com/sites/$site_id/webhooks"), array(
                "headers" => $this->get_webflow_headers(),
            ));

            return $this->handle_remote_response($response, new WP_Error('list_webhooks', __("The API key you used is invalid: failed to list webhooks", WEBFLOW_PAGES_TEXT_DOMAIN)));
        }

        /**
         * Removes a Webhook
         *
         * @param $site_id
         * @param $webhook_id
         *
         * @return array|mixed|object|WP_Error
         */
        public function remove_webhook($site_id, $webhook_id)
        {
            $response = wp_remote_request(esc_url_raw("https://api.webflow.com/sites/$site_id/webhooks/$webhook_id"), array(
                "headers" => $this->get_webflow_headers(),
                "method" => "DELETE"
            ));

            return $this->handle_remote_response($response, new WP_Error('remove_webhook', __("The API key you used is invalid: failed to remove webhooks", WEBFLOW_PAGES_TEXT_DOMAIN)));
        }

        /**
         * Sets a webhook to auto clean cache on site publish
         *
         * @param $site_id
         *
         * @return array|mixed|object|WP_Error
         */
        public function set_site_published_webhook($site_id)
        {

            $response = wp_remote_post(esc_url_raw("https://api.webflow.com/sites/$site_id/webhooks"), array(
                "headers" => $this->get_webflow_headers(),
                "data_format" => "body",
                "method" => "POST",
                "body" => json_encode(array(
                    "triggerType" => "site_publish",
                    "url" => get_rest_url(null, "wf/v1/wf-site-published"),
                ))
            ));

            return $this->handle_remote_response($response, new WP_Error('set_webhook', __("The API key you used is invalid: failed to add webhook", WEBFLOW_PAGES_TEXT_DOMAIN)));
        }

        /**
         *
         * Gets site domains
         *
         * @param $site_id
         *
         * returns an array of Object {_id: string, name: string}
         *
         * @return array|WP_Error
         */
        public function get_site_domains($site_id)
        {

            $response = wp_remote_get(esc_url_raw("https://api.webflow.com/sites/$site_id/domains"), array(
                "headers" => $this->get_webflow_headers(),
            ));

            return $this->handle_remote_response($response, new WP_Error('site_domains', __("The API key you used is invalid: failed to list your site domains", WEBFLOW_PAGES_TEXT_DOMAIN)));
        }

        /**
         *
         * Lists the collections of the CMS
         *
         * @param $site_id
         *
         * returns an array of objects {_id: string, lastUpdated: date, createdOn: date, name: string, slug: string, singularName: string}
         *
         * @return array|mixed|object|WP_Error
         */
        public function list_collections($site_id)
        {

            $response = wp_remote_get(esc_url_raw("https://api.webflow.com/sites/$site_id/collections"), array(
                "headers" => $this->get_webflow_headers(),
            ));

            return $this->handle_remote_response($response, new WP_Error('list_collections', __("The API key you used is invalid: failed to list your CMS Collections", WEBFLOW_PAGES_TEXT_DOMAIN)));
        }

        /**
         * Checks the response and responds accordingly
         *
         * @param $response WP_Error|array
         * @param $wp_error WP_Error|null
         *
         * @return array|mixed|object|WP_Error
         */
        private function handle_remote_response($response, $wp_error = null)
        {
            if (is_wp_error($response) || !isset($response['response']) || !isset($response['response']['code']) || !is_array($response)) {
                return $wp_error;
            }
            $response_code = $response['response']['code'];

            if (200 == $response_code) {

                return json_decode($response['body']);

            } else {
                try {
                    $error = json_decode($response['body']);
                    if (!$error && $ops = json_last_error_msg()) {
                        return $wp_error;
                    }
                    if (401 == $error->code) {
                        $this->remove_api_token();
                    }
                    return new WP_Error($error->code, $error->msg);
                } catch (Exception $e) {
                    // $message = $e->getMessage();
                    return $wp_error;
                }
            }
        }

    }

}