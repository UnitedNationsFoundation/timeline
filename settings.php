<?php
/*
    Plugin Name: Timeline
    Plugin URI:
    Description: Functions for timeline including CPT, taxonomy, custom fields, shortcode
    Version: 1.0
    Author: Melisa
*/
?>
<?php
    if( ! defined( 'ABSPATH' ) ){ exit; }

    if( !class_exists('Timeline') )
    {
        class Timeline
        {
            private $_post_type     =   'timeline_item';
            private $_taxonomy_name =   'timeline_date';
            private $_max_load_items=   3;

            public function __construct()
            {
                add_action( 'init',             array($this,'script_enqueuer'));
                add_action( 'init',             array($this,'register_post_type'));
                add_action( 'acf/init',         array($this,'add_acf_fields'));
				add_action( 'after_setup_theme',array($this,'custom_theme_setup'));
					
                add_shortcode( 'timeline',      array($this,'show_timeline'));

                add_action('wp_ajax_nopriv_load_timeline_posts',array($this,'load_posts'));
				add_action('wp_ajax_load_timeline_posts',		array($this,'load_posts'));
					
				//add_filter('oembed_result', array($this,'autoplay_youtube_embed_url'), 10, 3);
            }

            protected function getPostType()
            {
                return $this->_post_type;
            }

            protected function getTaxonomy()
            {
                return $this->_taxonomy_name;
            }

            public function getMaxLoadItems()
            {
                return $this->_max_load_items;
            }

            public function script_enqueuer()
            {
                $dir_url    =   plugin_dir_url( __FILE__ ).'assets/';

                $params     =	array(
                                    'ajax_url'      =>  admin_url( 'admin-ajax.php' ),
                                    'loading'       =>  '<div class="loading"></div>',
                                    'max_items'     =>  $this->getMaxLoadItems()
                                );

                wp_register_script('timeline_owl',       $dir_url.'owlcarousel/owl.carousel.min.js',    array('jquery') );
                wp_register_script('timeline_functions', $dir_url.'js/functions.js',                    array('jquery') );

                wp_localize_script('timeline_functions', 'TIMELINE',$params);

                wp_register_style( 'timeline_owl_stylesheet',       $dir_url.'owlcarousel/owl.carousel.min.css',        array(), '1.0', 'all');
                wp_register_style( 'timeline_owltheme_stylesheet',  $dir_url.'owlcarousel/owl.theme.default.min.css',   array(), '1.0', 'all');
                wp_register_style( 'timeline_stylesheet',           $dir_url.'css/style.css',                           array(), '1.0', 'all');
            }

            public function show_timeline($atts)
            {
                wp_enqueue_script( 'timeline_owl' );
                wp_enqueue_style(  'timeline_owl_stylesheet' );
                wp_enqueue_style(  'timeline_owltheme_stylesheet' );
                wp_enqueue_script( 'timeline_functions' );
                wp_enqueue_style(  'timeline_stylesheet' );

                include_once('templates/timeline.php');
            }

            public function register_post_type()
            {
                if( function_exists('acf_add_options_page') ):
                    acf_add_options_page(array(
                                            'page_title' 	=> 'Timeline Settings',
                                            'menu_title'	=> 'Timeline Settings',
                                            'menu_slug' 	=> 'timeline-settings',
                                            'capability'	=> 'edit_posts',
                                            'redirect'		=> false
                                        ));
                endif;

                register_post_type( $this->getPostType(),
                                    array(
                                        'labels'        =>  array(
                                                                'name'          => __('Timeline Items'),
                                                                'singular_name' => __('Timeline Item'),
                                                            ),
                                        'public'        =>  true,
                                        'has_archive'   =>  false
                                    ));

                register_taxonomy(  $this->getTaxonomy(),
                                    $this->getPostType(),
                                    array(
                                        'label'        => __( 'Date' ),
                                        'public'       => true,
                                        'rewrite'      => false,
                                        'hierarchical' => true
                                    ) );

                register_taxonomy_for_object_type($this->getTaxonomy(), $this->getPostType());
            }
				
			public function custom_theme_setup()
			{
				add_image_size( 'background-size', 2500, 2000 );
			}
				
            public function add_acf_fields()
            {
                if( !function_exists('acf_add_local_field_group') )
                    return;

                acf_add_local_field_group(  array(
                                                'key'       =>  'group_timeline_options',
                                                'title'     =>  'Timeline Data',
                                                'fields'    =>  array(
                                                                    array(
                                                                        'key'           =>  'field_timeline_big_title',
                                                                        'label'         =>  'Title',
                                                                        'name'          =>  'timeline_big_title',
                                                                        'type'          =>  'image',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  1,
                                                                        'conditional_logic' => 0,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'return_format' =>  'array',
                                                                        'preview_size'  =>  'thumbnail',
                                                                        'library'       =>  'all',
                                                                        'min_width'     =>  '',
                                                                        'min_height'    =>  '',
                                                                        'min_size'      =>  '',
                                                                        'max_width'     =>  '',
                                                                        'max_height'    =>  '',
                                                                        'max_size'      =>  '',
                                                                        'mime_types'    =>  '',
                                                                    ),
                                                                    array(
																		'key'           =>  'field_timeline_popup_label',
																		'label'         =>  'Popup Button Text',
																		'name'          =>  'timeline_popup_button_label',
																		'type'          =>  'text',
																		'required'      =>  1,
																	),
																	array(
																		'key'           =>  'field_timeline_popup_icon',
																		'label'         =>  'Popup Button Icon',
																		'name'          =>  'timeline_popup_button_icon',
																		'type'          =>  'image',
																		'required'      =>  1,
																		'return_format' =>  'array',
																		'preview_size'  =>  'thumbnail',
																		'library'       =>  'all'
																	),
                                                                    array(
																		'key'           =>  'field_timeline_popup_content_title',
																		'label'         =>  'Popup Title',
																		'name'          =>  'timeline_popup_content_title',
																		'type'          =>  'text',
																		'required'      =>  1,
																	),
																	array(
																		'key'           =>  'field_timeline_popup_content',
																		'label'         =>  'Popup Text',
																		'name'          =>  'timeline_popup_content',
																		'type'          =>  'wysiwyg',
																		'required'      =>  0,
																	),
																	array(
																		'key'           =>  'field_timeline_download_label',
																		'label'         =>  'Download Button Text',
																		'name'          =>  'timeline_download_button_label',
																		'type'          =>  'text',
																		'required'      =>  1,
																	),
																	array(
																		'key'           =>  'field_timeline_download_icon',
																		'label'         =>  'Download Button Icon',
																		'name'          =>  'timeline_download_button_icon',
																		'type'          =>  'file',
																		'required'      =>  1,
																		'return_format' =>  'array',
																		'preview_size'  =>  'thumbnail',
																		'library'       =>  'all'
																	),
                                                                    array(
                                                                        'key'           =>  'field_timeline_download_file',
                                                                        'label'         =>  'Download File',
                                                                        'name'          =>  'timeline_download_file',
                                                                        'type'          =>  'url',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'return_format' =>  'url',
                                                                        'mime_types'    =>  'pdf',
                                                                    ),
                                                                ),
                                                'location'  =>  array(
                                                                    array(
                                                                        array(
                                                                            'param'     =>  'options_page',
                                                                            'operator'  =>  '==',
                                                                            'value'     =>  'timeline-settings',
                                                                        ),
                                                                    ),
                                                                ),
                                                'menu_order'            =>  0,
                                                'position'              =>  'normal',
                                                'style'                 =>  'default',
                                                'label_placement'       =>  'top',
                                                'instruction_placement' =>  'label'
                                            ));

                acf_add_local_field_group(  array(
                                                'key'       =>  'group_timeline_item',
                                                'title'     =>  'Timeline Data',
                                                'fields'    =>  array(
                                                                    /*array(
                                                                        'key'           =>  'field_timeline_date',
                                                                        'label'         =>  'Date',
                                                                        'name'          =>  'timeline_date',
                                                                        'type'          =>  'date_picker',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  1,
                                                                        'conditional_logic' => 0,
                                                                        'wrapper'       =>  array(
                                                                                                'width' => '',
                                                                                                'class' => '',
                                                                                                'id'    => '',
                                                                                            ),
                                                                        'display_format'=>  'd/m/Y',
                                                                        'return_format' =>  'dmY',
                                                                        'first_day'     =>  1,
                                                                    ), */
                                                                    array(
                                                                        'key'           =>  'field_timeline_background_separator',
                                                                        'label'         =>  'Background',
                                                                        'name'          =>  'timeline_background_separator',
                                                                        'type'          =>  'Separator',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_background',
                                                                        'label'         =>  'Background Type',
                                                                        'name'          =>  'timeline_background',
                                                                        'type'          =>  'select',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  1,
                                                                        'choices'       =>  array(
                                                                                                'image'     =>  'Image',
                                                                                                'color'     =>  'Color',
                                                                                                'video'     =>  'Video',
                                                                                            ),
                                                                        'allow_null'    =>  0,
                                                                        'multiple'      =>  0,
                                                                        'ui'            =>  0,
                                                                        'return_format' => 'value',
                                                                        'ajax'          =>  0,
                                                                        'placeholder'   =>  '',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_background_image',
                                                                        'label'         =>  'Background Image',
                                                                        'name'          =>  'timeline_background_image',
                                                                        'type'          =>  'image',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_background',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'image',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'return_format' =>  'array',
                                                                        'preview_size'  =>  'thumbnail',
                                                                        'library'       =>  'all',
                                                                        'min_width'     =>  '',
                                                                        'min_height'    =>  '',
                                                                        'min_size'      =>  '',
                                                                        'max_width'     =>  '',
                                                                        'max_height'    =>  '',
                                                                        'max_size'      =>  '',
                                                                        'mime_types'    =>  '',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_background_color',
                                                                        'label'         =>  'Background Color',
                                                                        'name'          =>  'timeline_background_color',
                                                                        'type'          =>  'color_picker',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_background',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'color',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_background_video',
                                                                        'label'         =>  'Background Video',
                                                                        'name'          =>  'timeline_background_video',
                                                                        'type'          =>  'file',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_background',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'video',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'return_format' =>  'url',
                                                                        'mime_types'    =>  'mp4',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_background_video_url',
                                                                        'label'         =>  'Background Video Url',
                                                                        'name'          =>  'timeline_background_video_url',
                                                                        'type'          =>  'url',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_background',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'video',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),																	
                                                                    array(
                                                                        'key'           =>  'field_timeline_content_separator',
                                                                        'label'         =>  'Content',
                                                                        'name'          =>  'timeline_content_separator',
                                                                        'type'          =>  'Separator',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_overlay',
                                                                        'label'         =>  'Hide Header Overlay',
                                                                        'name'          =>  'timeline_overlay',
                                                                        'type'          =>  'true_false',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
																		'default_value' => 	0,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),																	
                                                                    array(
                                                                        'key'           =>  'field_timeline_overlay_color',
                                                                        'label'         =>  'Overlay Color',
                                                                        'name'          =>  'timeline_overlay_color',
                                                                        'type'          =>  'color_picker',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_overlay_opacity',
                                                                        'label'         =>  'Overlay Opacity',
                                                                        'name'          =>  'timeline_overlay_opacity',
                                                                        'type'          =>  'number',
                                                                        'instructions'  =>  'Add opacity percentage from 0 to 99',
                                                                        'required'      =>  0,
                                                                        'min'           =>  0,
                                                                        'max'           =>  99,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_content_type',
                                                                        'label'         =>  'Content Type',
                                                                        'name'          =>  'timeline_content_type',
                                                                        'type'          =>  'select',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  1,
                                                                        'choices'       =>  array(
                                                                                                'text'      =>  'Text',
                                                                                                'text-video'=>  'Text + Video',
                                                                                                'text-image'=>  'Text + Image',
                                                                                            ),
                                                                        'allow_null'    =>  0,
                                                                        'multiple'      =>  0,
                                                                        'ui'            =>  0,
                                                                        'return_format' => 'value',
                                                                        'ajax'          =>  0,
                                                                        'placeholder'   =>  '',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_image',
                                                                        'label'         =>  'Image',
                                                                        'name'          =>  'timeline_image',
                                                                        'type'          =>  'image',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_content_type',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'text-image',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'return_format' =>  'array',
                                                                        'preview_size'  =>  'thumbnail',
                                                                        'library'       =>  'all',
                                                                        'min_width'     =>  '',
                                                                        'min_height'    =>  '',
                                                                        'min_size'      =>  '',
                                                                        'max_width'     =>  '',
                                                                        'max_height'    =>  '',
                                                                        'max_size'      =>  '',
                                                                        'mime_types'    =>  '',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_video',
                                                                        'label'         =>  'Video',
                                                                        'name'          =>  'timeline_video',
                                                                        'type'          =>  'file',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_content_type',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'text-video',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'return_format' =>  'url',
                                                                        'mime_types'    =>  'mp4',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_video_url',
                                                                        'label'         =>  'Video',
                                                                        'name'          =>  'timeline_video_url',
                                                                        'type'          =>  'url',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_content_type',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'text-video',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_text_position',
                                                                        'label'         =>  'Text Position',
                                                                        'name'          =>  'timeline_text_position',
                                                                        'type'          =>  'select',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  1,
                                                                        'choices'       =>  array(
                                                                                                'top-right'     =>  'Top Right',
                                                                                                'top-left'      =>  'Top Left',
                                                                                                'center-right'  =>  'Center Right',
                                                                                                'center'        =>  'Center',
                                                                                                'center-left'   =>  'Center Left',
                                                                                                'bottom-right'  =>  'Bottom Right',
                                                                                                'bottom-left'   =>  'Bottom Left',
                                                                                            ),
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_content_type',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'text',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'allow_null'    =>  0,
                                                                        'multiple'      =>  0,
                                                                        'ui'            =>  0,
                                                                        'return_format' => 'value',
                                                                        'ajax'          =>  0,
                                                                        'placeholder'   =>  '',
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_text_backround_color',
                                                                        'label'         =>  'Text background color',
                                                                        'name'          =>  'timeline_text_background_color',
                                                                        'type'          =>  'color_picker',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_content_type',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'text',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_text_backround_opacity',
                                                                        'label'         =>  'Text background Opacity',
                                                                        'name'          =>  'timeline_text_background_opacity',
                                                                        'type'          =>  'number',
                                                                        'instructions'  =>  'Add opacity percentage from 0 to 99',
                                                                        'required'      =>  0,
                                                                        'conditional_logic'     =>  array(
                                                                                                        array(
                                                                                                            array(
                                                                                                                'field'     =>  'field_timeline_content_type',
                                                                                                                'operator'  =>  '==',
                                                                                                                'value'     =>  'text',
                                                                                                            ),
                                                                                                        ),
                                                                                                    ),
                                                                        'min'           =>  0,
                                                                        'max'           =>  99,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            )
                                                                    ),
                                                                    array(
                                                                        'key'           =>  'field_timeline_text',
                                                                        'label'         =>  'Text',
                                                                        'name'          =>  'timeline_text',
                                                                        'type'          =>  'wysiwyg',
                                                                        'instructions'  =>  '',
                                                                        'required'      =>  0,
                                                                        'conditional_logic' => 0,
                                                                        'wrapper'       =>  array(
                                                                                                'width' =>  '',
                                                                                                'class' =>  '',
                                                                                                'id'    =>  '',
                                                                                            ),
                                                                        'default_value' =>  '',
                                                                        'placeholder'   =>  '',
                                                                        'prepend'       =>  '',
                                                                        'append'        =>  '',
                                                                        'maxlength'     =>  '',
                                                                    ),
                                                                ),
                                                'location'  =>  array(
                                                                    array(
                                                                        array(
                                                                            'param'     =>  'post_type',
                                                                            'operator'  =>  '==',
                                                                            'value'     =>  $this->getPostType(),
                                                                        ),
                                                                    ),
                                                                ),
                                                'menu_order'            =>  0,
                                                'position'              =>  'normal',
                                                'style'                 =>  'default',
                                                'label_placement'       =>  'top',
                                                'instruction_placement' =>  'label',
                                                'hide_on_screen'        =>  array(
                                                                                'permalink',
                                                                                'the_content',
                                                                                'excerpt',
                                                                                'discussion',
                                                                                'comments',
                                                                                'revisions',
                                                                                'slug',
                                                                                'author',
                                                                                'format',
                                                                                'page_attributes',
                                                                                'featured_image',
                                                                                'categories',
                                                                                'tags',
                                                                                'send-trackbacks',
                                                                            )
                                            ));
            }

            public function getDates()
            {
                return get_terms(array(
                                    'taxonomy'      =>  $this->getTaxonomy(),
                                    'hide_empty'    =>  true,
                                    'orderby'       => 'menu_order',
                                    'order'         => 'ASC',
                                ));
            }

            public function getByDate($term_id, $limit = -1)
            {
                $args   =   array(
                                'post_type'         =>  $this->getPostType(),
                                'post_status'       =>  'publish',
                                'posts_per_page'    =>  $limit,
                                'tax_query'         =>  array(
                                                            array(
                                                                'taxonomy'  =>  $this->getTaxonomy(),
                                                                'field'     =>  'term_id',
                                                                'terms'     =>  $term_id,
                                                            )
                                                        ),
								'orderby'       	=> 	'menu_order',
								'order'         	=> 	'ASC'
                                /*'meta_key'		    =>  'timeline_date',
                                'orderby'		    =>  'meta_value_num',
                                'meta_type'         =>  'DATE',
                                'order'			    =>  'ASC'*/
                            );
					
                $query  =   new WP_Query($args);
					
                return $query;
            }

            public function load_posts()
            {
                $term_id=   $_POST['term_id'];
                $key    =   $_POST['key'];
				$result =	array();

				try{
					$query  =   $this->getByDate($term_id);

					ob_start();
					if( $query->have_posts() ):
						$total_posts    =   $query->post_count;
						$addClass       =   ( $total_posts > 4 )    ?   ' multiple-owl-carousel' :   ( ($total_posts == 1) ? ' single-owl-carousel':'');

						while( $query->have_posts() ):
							$query->the_post();
							$content_type   =   get_field('timeline_content_type');
							include('templates/content-type/'.$content_type.'.php');
						endwhile;
					endif;
					wp_reset_postdata();

					$html 	=	ob_get_contents();
					ob_end_clean();
					$result	=	array('status' => 'ok', 'content' => $html, 'classes' => 'loaded timeline-carousel '.$addClass);
				}catch(Exception $e){
					$result	=	array('status' => 'err','err' => $e->getMessage());
				}

				wp_send_json($result);
                exit;
            }
			
			public function autoplay_youtube_embed_url($html, $url, $args)
			{
				if( is_array($args) and array_key_exists('autoplay',$args) && ($args['autoplay'] == 1) )
					return str_replace("?feature=oembed", "?feature=oembed&autoplay=1", $html);
				return $html;
			}
        }
    }

    new Timeline();
?>
