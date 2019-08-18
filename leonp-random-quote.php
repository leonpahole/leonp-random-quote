<?php

/*
 * Plugin Name: Random Quote
 * Description: Prints random quote from specified quotes. Done for educational purposes as part of me learning how to write WP plugins.
 * Author: Leon Pahole
 */

/*
TODO (maybe)
 * handle deletion and disable of plugin
 * dynamic fields
 * better styling
 * unhardcode strings
 */

// if class exists, it will cause naming problems
if (!class_exists('LeonP_RandomQuote')) {

    class LeonP_RandomQuote
    {
        private $default_quote_count = 1; // default quote fields
        private $quote_count = 1; // specified quote count

        public function __construct()
        {
            add_action('admin_menu', array($this, 'add_menu_item'));
            add_action('admin_init', array($this, 'add_settings'));

            add_shortcode('leonp_random_quote', array($this, 'shortcode_random_quote'));

            add_action('wp_enqueue_scripts', array($this, 'add_rq_styles'));
        }

        // add styles to page (only non admin)
        // even if shortcode is not on the page, it is a good idea to load styles since browser caches the file anyway
        public function add_rq_styles()
        {
            if (!is_admin()) {
                wp_register_style('leonp_rq_css', plugins_url('leonp-random-quote.css', __FILE__));
                wp_enqueue_style('leonp_rq_css');
            }
        }

        // obtain specified quote count from options db
        public function set_quote_count()
        {
            $this->quote_count = get_option('leonp_rq_count') ?
                get_option('leonp_rq_count') :
                $this->default_quote_count;
        }

        // add admin UI menu item
        public function add_menu_item()
        {
            add_menu_page(
                'Random quote editor',
                'Random quote editor',
                'manage_options',
                'leonp-rq',
                array($this, 'render_menu'),
                'dashicons-format-quote');
        }

        // add admin UI fields when menu item is clicked
        public function add_settings()
        {
            $this->set_quote_count();

            // section - groups multiple fields
            add_settings_section(
                'leonp_rq_section',
                null,
                null,
                'leonp-rq');

            // field for count
            add_settings_field(
                'leonp_rq_count',
                'Quote count (enter and save for new fields)',
                array($this, 'render_field_count'),
                'leonp-rq',
                'leonp_rq_section');

            register_setting('leonp_rq', 'leonp_rq_count');

            // multiple fields for quotes (defined by count)
            for ($i = 1; $i <= $this->quote_count; $i++) {

                add_settings_field(
                    "leonp_rq_q{$i}",
                    "Quote {$i}",
                    array($this, "render_quote_field"),
                    'leonp-rq',
                    'leonp_rq_section',
                    $i);

                register_setting('leonp_rq', "leonp_rq_q{$i}");
            }
        }

        // render quote count input
        public function render_field_count()
        {
            ?>
            <input min="1" name="leonp_rq_count"
                   id="leonp_rq_count" type="number"
                   value="<?php echo $this->quote_count; ?>">
            <?php
        }

        // render quote input (index specifies order of the quote in inputs)
        public function render_quote_field($index)
        {
            $field_name = "leonp_rq_q{$index}";

            ?>
            <input value="<?php echo get_option($field_name) ?>" name="<?php echo $field_name; ?>"
                   id="<?php echo $field_name; ?>" type="text">
            <?php
        }

        // let settings API render fields
        public function render_menu()
        {
            ?>
            <div>
                <h2>Random quote editor</h2>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('leonp_rq');
                    do_settings_sections('leonp-rq');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        // shortcode for displaying random quotes
        // args -> count: amount of quotes to display (default: 1)
        public function shortcode_random_quote($atts = [], $content = '', $tag = '')
        {
            $this->set_quote_count();

            $atts = array_change_key_case((array)$atts, CASE_LOWER);

            $rq_atts = shortcode_atts([
                'count' => 1,
            ], $atts, $tag);

            $quote_amount = intval($rq_atts['count']);

            if(!$quote_amount) $quote_amount = 1;

            $iter_count_limit = $this->quote_count * $quote_amount;

            $quotes = array();

            $iter_count = 0;

            for ($i = 0; $i < $quote_amount; $i++) {

                while (count($quotes) < $quote_amount && $iter_count < $iter_count_limit) {

                    $random_quote_index = rand(1, $this->quote_count);

                    $random_quote = get_option("leonp_rq_q{$random_quote_index}");

                    if ($random_quote) {
                        array_push($quotes, $random_quote);
                    }

                    $iter_count++;
                }
            }

            $final_quote_string = '';

            foreach ($quotes as $quote) {

                $final_quote_string .= '<p class="leonp-quote">' . $quote . '</p>';
            }

            return '<div class="quote-container">' . $final_quote_string . '</div>';
        }
    }

    $leonp_randomquote = new LeonP_RandomQuote();
}
