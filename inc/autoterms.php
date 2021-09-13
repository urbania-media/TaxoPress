<?php

class SimpleTags_Autoterms
{

    const MENU_SLUG = 'st_options';

    // class instance
    static $instance;

    // WP_List_Table object
    public $terms_table;

    /**
     * Constructor
     *
     * @return void
     * @author Olatechpro
     */
    public function __construct()
    {

        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        // Admin menu
        add_action('admin_menu', [$this, 'admin_menu']);
        // Javascript
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'], 11);

    }

    /**
     * Init somes JS and CSS need for this feature
     *
     * @return void
     * @author Olatechpro
     */
    public static function admin_enqueue_scripts()
    {

        // add JS for manage click tags
        if (isset($_GET['page']) && $_GET['page'] == 'st_autoterms') {
            wp_enqueue_style('st-taxonomies-css');
        }
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Add WP admin menu for Tags
     *
     * @return void
     * @author Olatechpro
     */
    public function admin_menu()
    {
        $hook = add_submenu_page(
            self::MENU_SLUG,
            __('Auto Terms', 'simple-tags'),
            __('Auto Terms', 'simple-tags'),
            'simple_tags',
            'st_autoterms',
            [
                $this,
                'page_manage_autoterms',
            ]
        );

        if(taxopress_is_screen_main_page()){
          add_action("load-$hook", [$this, 'screen_option']);
        }
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => __('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_autoterms_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Autoterms_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_autoterms()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__);

        if (!isset($_GET['add'])) {
            //all tax
            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php _e('Auto Terms', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                <div class="taxopress-description">
                    <?php esc_html_e('This feature allows Wordpress to look into post content and title for specified terms when saving post.', 'simple-tags'); ?>
                    <br />
                    <?php esc_html_e('If your post content or title contains the word "WordPress" and you have "wordpress" in auto terms list, TaxoPress will automatically add "wordpress" as term for this post.', 'simple-tags'); ?>
                </div>


                <?php
                if (isset($_REQUEST['s']) && $search = esc_attr(wp_unslash($_REQUEST['s']))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;',
                            'simple-tags') . '</span>', $search);
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(__('Search Auto Terms', 'simple-tags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
                        <form action="<?php echo add_query_arg('', '') ?>" method="post">
                            <?php $this->terms_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php __('Description here.', 'simple-tags') ?></p>
                        </div>
                    </div>


                </div>


            </div>
        <?php } else {
            if ($_GET['add'] == 'new_item') {
                //add/edit taxonomy
                $this->taxopress_manage_autoterms();
                echo '<div>';
            }
        } ?>


        <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
        <?php
    }


    /**
     * Create our settings page output.
     *
     * @internal
     */
    public function taxopress_manage_autoterms()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php

        $autoterms      = taxopress_get_autoterm_data();
        $autoterm_edit  = false;
        $autoterm_limit = false;

        if ('edit' === $tab) {


            $selected_autoterm = taxopress_get_current_autoterm();

            if ($selected_autoterm && array_key_exists($selected_autoterm, $autoterms)) {
                $current       = $autoterms[$selected_autoterm];
                $autoterm_edit = true;
            }

        }


        if (!isset($current['title']) && count($autoterms) > 0 && apply_filters('taxopress_autoterms_create_limit', true)) {
            $autoterm_limit = true;
        }


        $ui = new taxopress_admin_ui();
        ?>


        <div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo __('Manage Auto Terms', 'simple-tags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="">


                <div class="tagcloudui st-tabbed">


                    <div class="autoterms-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($autoterm_edit) {
                                            $active_tab = ( isset($current['active_tab']) && !empty(trim($current['active_tab'])) ) ? $current['active_tab'] : 'autoterm_general';
                                            echo esc_html__('Edit Auto Terms', 'simple-tags');
                                            echo '<input type="hidden" name="edited_autoterm" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autoterm[ID]" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autoterm[active_tab]" class="taxopress-active-subtab" value="'.$active_tab.'" />';
                                        } else {
                                            $active_tab = 'autoterm_general';
                                            echo '<input type="hidden" name="taxopress_autoterm[active_tab]" class="taxopress-active-subtab" value="" />';
                                            echo esc_html__('Add new Auto Terms', 'simple-tags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($autoterm_limit) {
                                            echo '<div class="st-taxonomy-content"><div class="taxopress-warning upgrade-pro">
                                            <p>

                                            <h2 style="margin-bottom: 5px;">' . __('To create more Auto Terms, please upgrade to TaxoPress Pro.',
                                                    'simple-tags') . '</h2>
                                            ' . __('With TaxoPress Pro, you can create unlimited Auto Terms. You can create Auto Terms for any taxonomy.',
                                                    'simple-tags') . '

                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li class="autoterm_general_tab <?php echo $active_tab === 'autoterm_general' ? 'active' : ''; ?>" data-content="autoterm_general">
                                                    <a href="#autoterm_general"><span><?php esc_html_e('General',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_terms_tab <?php echo $active_tab === 'autoterm_terms' ? 'active' : ''; ?>" data-content="autoterm_terms">
                                                    <a href="#autoterm_terms"><span><?php esc_html_e('Terms to Use',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_options_tab <?php echo $active_tab === 'autoterm_options' ? 'active' : ''; ?>" data-content="autoterm_options">
                                                    <a href="#autoterm_options"><span><?php esc_html_e('Options',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_oldcontent_tab <?php echo $active_tab === 'autoterm_oldcontent' ? 'active' : ''; ?>" data-content="autoterm_oldcontent">
                                                    <a href="#autoterm_oldcontent"><span><?php esc_html_e('Old Content',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table autoterm_general"
                                                       style="<?php echo $active_tab === 'autoterm_general' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                    echo $ui->get_tr_start();

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];

                                                    $selected           = (isset($current) && isset($current['use_auto_terms'])) ? taxopress_disp_boolean($current['use_auto_terms']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['use_auto_terms'] : '1';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'use_auto_terms',
                                                        'class'      => 'use_auto_terms',
                                                        'labeltext'  => esc_html__('Enable this auto term', 'simple-tags'),
                                                        'aftertext'  => '',
                                                        'selections' => $select,
                                                    ]);


                                                    echo $ui->get_th_start();
                                                    echo $ui->get_label('title', esc_html__('Title',
                                                            'simple-tags')) . $ui->get_required_span();
                                                    echo $ui->get_th_end() . $ui->get_td_start();

                                                    echo $ui->get_text_input([
                                                        'namearray'   => 'taxopress_autoterm',
                                                        'name'        => 'title',
                                                        'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                        'maxlength'   => '32',
                                                        'helptext'    => '',
                                                        'required'    => true,
                                                        'placeholder' => false,
                                                        'wrap'        => false,
                                                    ]);


                                                    $options = [];
                                                    foreach (get_all_taxopress_taxonomies() as $_taxonomy) {
                                                        $_taxonomy = $_taxonomy->name;
                                                        $tax       = get_taxonomy($_taxonomy);
                                                        if (empty($tax->labels->name)) {
                                                            continue;
                                                        }
                                                        if ($tax->name === 'post_tag') {
                                                            $options[] = [
                                                                'attr'    => $tax->name,
                                                                'text'    => $tax->labels->name,
                                                                'default' => 'true',
                                                            ];
                                                        } else {
                                                            $options[] = [
                                                                'attr' => $tax->name,
                                                                'text' => $tax->labels->name,
                                                            ];
                                                        }
                                                    }

                                                    $select             = [
                                                        'options' => $options,
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['taxonomy']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['taxonomy'] : '';
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
                                                        'labeltext'  => esc_html__('Taxonomy', 'simple-tags'),
                                                        'required'   => true,
                                                        'selections' => $select,
                                                    ]);

                                                    /**
                                                     * Filters the arguments for post types to list for taxonomy association.
                                                     *
                                                     *
                                                     * @param array $value Array of default arguments.
                                                     */
                                                    $args = apply_filters('taxopress_attach_post_types_to_taxonomy',
                                                        ['public' => true]);

                                                    // If they don't return an array, fall back to the original default. Don't need to check for empty, because empty array is default for $args param in get_post_types anyway.
                                                    if (!is_array($args)) {
                                                        $args = ['public' => true];
                                                    }
                                                    $output = 'objects'; // Or objects.

                                                    /**
                                                     * Filters the results returned to display for available post types for taxonomy.
                                                     *
                                                     * @param array $value Array of post type objects.
                                                     * @param array $args Array of arguments for the post type query.
                                                     * @param string $output The output type we want for the results.
                                                     */
                                                    $post_types = apply_filters('taxopress_get_post_types_for_taxonomies',
                                                        get_post_types($args, $output), $args, $output);

                                                    $term_auto_locations = [];
                                                    foreach ($post_types as $post_type) {
                                                        $term_auto_locations[$post_type->name] = $post_type->label;
                                                    }

                                                    echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Post Types',
                                                            'simple-tags') . '</label> </th><td>
                                                    <table class="visbile-table">';
                                                    foreach ($term_auto_locations as $key => $value) {


                                                        echo '<tr valign="top"><th scope="row"><label for="' . $key . '">' . $value . '</label></th><td>';

                                                        echo $ui->get_check_input([
                                                            'checkvalue' => $key,
                                                            'checked'    => (!empty($current['post_types']) && is_array($current['post_types']) && in_array($key,
                                                                    $current['post_types'], true)) ? 'true' : 'false',
                                                            'name'       => $key,
                                                            'namearray'  => 'post_types',
                                                            'textvalue'  => $key,
                                                            'labeltext'  => "",
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';


                                                    }
                                                    echo '</table></td></tr>';


                                                    echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>




                                                <table class="form-table taxopress-table autoterm_terms"
                                                       style="<?php echo $active_tab === 'autoterm_terms' ? '' : 'display:none;'; ?>">
                                                    <?php

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autoterm_useall'])) ? taxopress_disp_boolean($current['autoterm_useall']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_useall'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_useall',
                                                        'class'      => 'autoterm_useall',
                                                        'labeltext'  => esc_html__('All terms', 'simple-tags'),
                                                        'aftertext'  => __('Use all the terms in the selected taxonomy. (Warning, this option can increases the CPU consumption a lot if you have many terms)', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autoterm_useonly'])) ? taxopress_disp_boolean($current['autoterm_useonly']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_useonly'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_useonly',
                                                        'class'      => 'autoterm_useonly',
                                                        'labeltext'  => esc_html__('Specific terms', 'simple-tags'),
                                                        'aftertext'  => __('Use this option if you don\'t want to use all the terms in the selected taxonomy. You can enter terms to use.', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $specific_terms = ( isset($current) && isset($current['specific_terms']) && is_array($current['specific_terms']) ) ? (array)$current['specific_terms'] : [];

                                                    echo '<tr valign="top"><th scope="row"><label for=""></label></th><td>';
                                                    echo '<div class="auto-terms-to-use-error"> '.__('Please choose an option for "Terms to use"', 'simple-tags').' </div>';

                                                    if(count($specific_terms) > 0){
                                                        foreach($specific_terms as $specific_term){
                                                            echo '<div class="st-autoterms-single-specific-term">
                                                            <input type="text" class="specific_terms_input" name="specific_terms[]" maxlength="32" placeholder="'. esc_attr(__('Term name', 'simple-tags')) .'" value="'. esc_attr($specific_term) .'"> &nbsp; &nbsp; <input type="submit" class="button remove-specific-term" value="'. esc_attr(__('Remove', 'simple-tags')) .'">
                                                        </div>';
                                                        }
                                                    }else{
                                                        echo '<div class="st-autoterms-single-specific-term">
                                                            <input type="text" class="specific_terms_input" name="specific_terms[]" maxlength="32" placeholder="'. esc_attr(__('Term name', 'simple-tags')) .'"> &nbsp; &nbsp; <input type="submit" class="button remove-specific-term" value="'. esc_attr(__('Remove', 'simple-tags')) .'">
                                                        </div>';
                                                    }
                                                    
                                                    echo '<div class="st-autoterms-single-specific-term new">
                                                            <input style="visibility: hidden;" type="text" class=""> &nbsp; &nbsp; <input type="submit" class="button add-specific-term" value="'. esc_attr(__('Add new', 'simple-tags')) .'"
                                                             data-placeholder="' .esc_attr(__('Term name', 'simple-tags')) .'"
                                                             data-text="'. esc_attr(__('Remove', 'simple-tags')) .'">
                                                        </div>';
                                                    
                                                    echo '</td></tr>';

                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table autoterm_options"
                                                       style="<?php echo $active_tab === 'autoterm_options' ? '' : 'display:none;'; ?>">
                                                    <?php

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autoterm_target'])) ? taxopress_disp_boolean($current['autoterm_target']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_target'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_target',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Target', 'simple-tags'),
                                                        'aftertext'  => __('Autotag only Posts without terms.', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autoterm_word'])) ? taxopress_disp_boolean($current['autoterm_word']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_word'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_word',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Whole Word ?', 'simple-tags'),
                                                        'aftertext'  => __('Autotag Posts only when terms found in the content are the same word.', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autoterm_hash'])) ? taxopress_disp_boolean($current['autoterm_hash']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_hash'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_hash',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Support Hashtag format ?', 'simple-tags'),
                                                        'aftertext'  => __('When the whole word option is enabled, hashtag will not be autotaged because of # prefix. Selecting this option fixes the issue!', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autoterm_oldcontent"
                                                       style="<?php echo $active_tab === 'autoterm_oldcontent' ? '' : 'display:none;'; ?>">

                                                       <tr valign="top"><th scope="row"><label><?php echo __('Auto terms old content', 'simple-tags'); ?></label></th>
                                                       <td>
                                                           <input type="submit" class="button taxopress-autoterm-all-content" value="<?php echo esc_attr(__('Auto terms all content »', 'simple-tags')); ?>">
                                                           <span class="spinner taxopress-spinner"></span>

                                                           <p class="taxopress-field-description description">
                                                               <?php echo __('TaxoPress can also tag all existing contents of your blog.', 'simple-tags'); ?>
                                                               
                                                               <br /> <strong style="color:red;"><?php echo __('Update or Save other tabs changes before running this function!!!', 'simple-tags'); ?></strong>
                                                            </p>
                                            
                                                            <div class="auto-term-content-result-title"></div>

                                                            </div>

                                                            <ul class="auto-term-content-result"></ul>
                                                        </td></tr>
                                                    <?php

                                                    ?>

                                                </table>


                                            </div>


                                        <?php }//end new fields
                                        ?>


                                        <div class="clear"></div>


                                    </div>
                                </div>
                            </div>


                            <?php if ($autoterm_limit) { ?>

                                <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                    <div class="pp-version-notice-bold-purple-message">You're using TaxoPress Free.
                                        The Pro version has more features and support.
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/pro" target="_blank">Upgrade to Pro</a>
                                    </div>
                                </div>

                            <?php } ?>
                            <?php
                            /**
                             * Fires after the default fieldsets on the taxonomy screen.
                             *
                             * @param taxopress_admin_ui $ui Admin UI instance.
                             */
                            do_action('taxopress_taxonomy_after_fieldsets', $ui);
                            ?>

                        </div>
                    </div>


                </div>

                <div class="taxopress-right-sidebar">
                    <div class="taxopress-right-sidebar-wrapper" style="min-height: 205px;">


                        <?php
                        if (!$autoterm_limit) { ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_autoterm_nonce_action',
                                    'taxopress_addedit_autoterm_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-autoterm-submit"
                                           name="autoterm_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Auto terms',
                                               'simple-tags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-autoterm-submit"
                                           name="autoterm_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Auto terms',
                                               'simple-tags')); ?>"/>
                                <?php } ?>


                                <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                       value="<?php echo esc_attr($tab); ?>"/>
                            </p>

                            <?php
                        }
                        ?>

                    </div>

                </div>

                <div class="clear"></div>


            </form>

        </div><!-- End .wrap -->

        <div class="clear"></div>

        <?php # Modal Windows; ?>
<div class="remodal" data-remodal-id="taxopress-modal-alert"
     data-remodal-options="hashTracking: false, closeOnOutsideClick: false">
     <div class="" style="color:red;"><?php echo __('Please complete the following required fields to save your changes:', 'simple-tags'); ?></div>
    <div id="taxopress-modal-alert-content"></div>
    <br>
    <button data-remodal-action="cancel" class="remodal-cancel"><?php echo __('Okay', 'simple-tags'); ?></button>
</div>

<div class="remodal" data-remodal-id="taxopress-modal-confirm"
     data-remodal-options="hashTracking: false, closeOnOutsideClick: false">
    <div id="taxopress-modal-confirm-content"></div>
    <br>
    <button data-remodal-action="cancel" class="remodal-cancel"><?php echo __('No', 'simple-tags'); ?></button>
    <button data-remodal-action="confirm"
            class="remodal-confirm"><?php echo __('Yes', 'simple-tags'); ?></button>
</div>

        <?php
    }

}