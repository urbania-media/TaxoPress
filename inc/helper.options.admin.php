<?php
require_once STAGS_DIR . '/modules/taxopress-ai/classes/TaxoPressAiUtilities.php';

$taxopress_ai_tabs = [];
$taxopress_ai_tabs['post_terms'] = esc_html__('Manage Post Terms', 'simple-tags');
$taxopress_ai_tabs['suggest_local_terms'] = esc_html__('Suggest Existing Terms', 'simple-tags');
$taxopress_ai_tabs['existing_terms'] = esc_html__('Show All Existing Terms', 'simple-tags');
$taxopress_ai_tabs['open_ai'] = esc_html__('OpenAI', 'simple-tags');
$taxopress_ai_tabs['ibm_watson'] = esc_html__('IBM Watson', 'simple-tags');
$taxopress_ai_tabs['dandelion'] = esc_html__('Dandelion', 'simple-tags');
$taxopress_ai_tabs['open_calais'] = esc_html__('LSEG / Refinitiv', 'simple-tags');

$taxopress_ai_fields = [];
$pt_index = 0;
foreach (TaxoPressAiUtilities::get_post_types_options() as $post_type => $post_type_object) {
    $hidden_field = ($pt_index === 0) ? '' : 'st-hide-content';

    $default_taxonomy_options = [];
    foreach (get_object_taxonomies($post_type, 'objects') as $tax_key => $tax_object) {
        if (!in_array($tax_key, ['post_format']) && (!empty($tax_object->show_ui) || !empty(SimpleTags_Plugin::get_option_value('taxopress_ai_' . $post_type . '_support_private_taxonomy')))) {
            $default_taxonomy_options[$tax_key] = $tax_object->labels->name. ' ('.$tax_object->name.')';
        }
    }

    if (!empty($default_taxonomy_options)) { // This feature only matter if a post has taxonomy
        $taxopress_ai_fields[] = array(
            'enable_taxopress_ai_' . $post_type . '_metabox',
            sprintf(esc_html__('%1s Metabox', 'simple-tags'), esc_html($post_type_object->labels->name)),
            'checkbox',
            '1',
            sprintf(esc_html__('Enable the TaxoPress AI metabox on the %1s screen.', 'simple-tags'), esc_html($post_type_object->labels->name)),
            'taxopress-ai-tab-content taxopress-ai-'. $post_type .'-content '. $hidden_field .''
        );

        $taxopress_ai_fields[] = array(
             'taxopress_ai_' . $post_type . '_support_private_taxonomy',
            sprintf(esc_html__('Show %1s Private Taxonomies in Metabox', 'simple-tags'), esc_html($post_type_object->labels->name)),
            'checkbox',
            '1',
            sprintf(esc_html__('Add support for %1s private taxonomies in TaxoPress AI.', 'simple-tags'), esc_html($post_type_object->labels->name)),
            'taxopress-ai-tab-content-sub taxopress-ai-'. $post_type .'-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content'
        );

        // add taxonomy
        $taxopress_ai_fields[] = array(
            'taxopress_ai_' . $post_type . '_metabox_default_taxonomy',
            '<div class="taxopress-ai-tab-content-sub taxopress-settings-subtab-title taxopress-ai-'. $post_type .'-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content">' . esc_html__('Metabox Default Taxonomy', 'simple-tags') . '</div>',
            'select',
            $default_taxonomy_options,
            '',
            'taxopress-ai-tab-content-sub taxopress-ai-'. $post_type .'-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content'
            );

        // add feature tab
        $tab_field_options = [];
        foreach ($taxopress_ai_tabs as $taxopress_ai_tab => $taxopress_ai_tab_label) {
            $tab_field_options['enable_taxopress_ai_' . $post_type . '_' . $taxopress_ai_tab . '_tab'] = $taxopress_ai_tab_label;
        }
        $taxopress_ai_fields[] = array(
            'enable_taxopress_ai_' . $post_type . '_tab',
            '<div class="taxopress-ai-tab-content-sub taxopress-settings-subtab-title taxopress-ai-'. $post_type .'-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content">' . esc_html__('Metabox Features', 'simple-tags') . '</div>',
            'taxopress_ai_multiple_checkbox',
            $tab_field_options,
            '<p class="taxopress-ai-tab-content-sub taxopress-settings-description taxopress-ai-'. $post_type .'-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field description st-subhide-content">' . esc_html__('Features that require an API key will not display without a valid key.', 'simple-tags') . '</p>',
            'taxopress-ai-tab-content-sub taxopress-ai-'. $post_type .'-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content'
        );
    }
    $pt_index++;
}

$all_taxonomies = get_taxonomies([], 'objects');
$linked_terms_taxonomy_options = [];
foreach ($all_taxonomies as $tax) {
    if (in_array($tax->name, ['author', 'post_format', 'nav_menu', 'link_category', 'wp_theme', 'wp_template_part_area', 'wp_pattern_category'])) {
        continue;
    }
    $linked_terms_taxonomy_options[$tax->name] = $tax->label;
}

return apply_filters('taxopress_admin_options', array(
    // post tab
    'posts'       => array(
        array(
            'post_terms_filter_format',
            __('Terms Filter display:', 'simple-tags'),
            'radio',
            array(
                'term_name'  => __('Term Name', 'simple-tags'),
                'term_name_taxonomy_name'   => __('Term Name + Taxonomy Name', 'simple-tags'),
                'term_name_taxonomy_slug' => __('Term Name + Taxonomy Slug', 'simple-tags'),
            ),
            __('This controls the details that appear in the "Terms Filter" display and can help if you have terms with similar names.', 'simple-tags'),
            ''
        ),
        array(
            'post_terms_taxonomy_type',
            __('Terms Filter taxonomy:', 'simple-tags'),
            'radio',
            array(
                'public'  => __('Public Taxonomies', 'simple-tags'),
                'private'   => __('Private Taxonomies', 'simple-tags'),
                'term_and_private' => __('Public Taxonomies and Private Taxonomies', 'simple-tags'),
            ),
            __('This controls the taxonomy terms that appear on the "Posts" screen.', 'simple-tags'),
            ''
        ),
    ),

    
    // linked terms tab
    'linked_terms'       => array(
        array(
            'linked_terms_taxonomies',
            __('Enable Taxonomies:', 'simple-tags'),
            'multiselect',
            $linked_terms_taxonomy_options,
            __('This control taxonomies enabled for Linked Terms feature.', 'simple-tags'),
            ''
        )
    ),

    // taxopress ai tab
    'taxopress-ai' => $taxopress_ai_fields,
)
);
