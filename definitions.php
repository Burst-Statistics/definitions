<?php

/**
 * Plugin Name: Definitions hyperlinks
 * Plugin URI: http://www.rogierlankhorst.com/definitions-hyperlinks
 * Description: Plugin to autoreplace in the content of a page or post every instance of a word that is defined in the definitions 
 * Version: 1.0.0
 * Text Domain: rldh-definitions-hyperlinks
 * Domain Path: /lang
 * Author: Rogier Lankhorst
 * Author URI: http://www.rogierlankhorst.com
 * License: GPL2
 */

/*  Copyright 2014  Rogier Lankhorst  (email : rogier@rogierlankhorst.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    rldh: Rogier Lankhorst Definitions hyperlinks
*/


/*
    available filters

    CONTENT
    rldh_replace_definitions: the content of the current post, with definitions replaced by tooltips and links to definitions pages
    rldh_tooltip_html       : the tooltip markup
    rldh_definitions_query  : the query that returns the list of definitions
    rldh_post_type_args
    rldh_post_type_labels
    rldh_post_type_support

    WIDGET
    rldh_widget_title       : the widget title
    rldh_definitions_widget_list_query    : the query that returns the definitions cloud
    rldh_definitionscloud
    
    SHORTCODE
    rldh_definitions_shortcode_list_query : the query that returns the shortcode list
    rldh_insert_shortcode: just before the shortcode is inserted

    Deregistering styles and scripts
    JS:  wp_deregister_script($handle ), where $handle = rldh-tooltipjs, or rldh-js. 
    CSS: wp_deregister_style( $handle ) where $handle = rldh-tooltipcss
*/
defined('ABSPATH') or die("you do not have acces to this page!");

$new_version = '1.0.0'; //if version changes, change upgrade logic in global variables file

class rldh_definition {
    public $plugin_url;
    const tooltip_html = '<a href="rldh_REPLACE_URL" class="rldh_definition" data-toggle="tooltip" title="rldh_REPLACE_TITLE">rldh_REPLACE_LINK</a>';
    const default_nr_of_definitions = 5;//used for the default nr of defs in the widget
    const max_nr_of_columns_in_shortcode_list = 12;

    public function __construct()
    {
        $this->plugin_url = trailingslashit(WP_PLUGIN_URL).trailingslashit(dirname(plugin_basename(__FILE__)));
        add_action('init', array($this, 'load_translation'));
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('init',array($this, 'create_definitions_post_type'));
        add_filter( 'the_content', array($this, 'replace_definitions_with_links'));
        add_shortcode('rldh-definitions-list', array($this, 'add_shortcode'));
    }

    public function load_translation()
    {
        load_plugin_textdomain('rldh-definitions-hyperlinks', FALSE, dirname(plugin_basename(__FILE__)).'/lang/');
    }

    public function enqueue_assets() 
    {
        wp_register_style( 'rldh-tooltipcss', $this->plugin_url . 'css/tooltip.css');
        wp_enqueue_style( 'rldh-tooltipcss' );
        wp_enqueue_script( "rldh-tooltipjs", $this->plugin_url."js/tooltip.js",array('jquery'),'1.0.0', true );
        wp_enqueue_script( "rldh-js", $this->plugin_url."js/main.js",array('jquery'),'1.0.0', true );
    }

    public function create_definitions_post_type() {
        $labels =  apply_filters( 'rldh_post_type_labels',array(
            'name'              => __('Definitions','rldh-definitions-hyperlinks'),
            'singular_name'     => __('definition','rldh-definitions-hyperlinks'),
            'add_new'           => __('New definition','rldh-definitions-hyperlinks'),
            'add_new_item'      => __('Add new definition','rldh-definitions-hyperlinks'),
            'parent_item_colon' => __('definition','rldh-definitions-hyperlinks'),
            'parent'            => __('definition parentitem','rldh-definitions-hyperlinks'),
            'edit_item'         => __('Edit definition', 'rldh-definitions-hyperlinks' ),
            'new_item'          => __('New definition', 'rldh-definitions-hyperlinks' ),
            'view_item'         => __('View definition', 'rldh-definitions-hyperlinks' ),
            'search_items'      => __('Search definitions', 'rldh-definitions-hyperlinks' ),
            'not_found'         => __('No definitions found', 'rldh-definitions-hyperlinks' ),
            'not_found_in_trash'=> __('No definitions found in trash', 'rldh-definitions-hyperlinks' ),
        ));
        $rewrite = array(
            'slug' => __('definitions','rldh-definitions-hyperlinks'),
            'pages'=> true
        );
        $supports = apply_filters( 'rldh_post_type_support', array(
            'title',
            'editor',
            'thumbnail',
            'revisions',
            'page-attributes',
            'excerpt'
        ));

        $args = apply_filters( 'rldh_post_type_args', array(
            'labels'                => $labels,
            'public'                => true,
            'exclude_from_search'   => false,
            'show_ui'               => true,
            'show_in_admin_bar'     => true,
            'rewrite'               => $rewrite,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-lightbulb',//plugins_url( 'css/images/menu-icon.png', dirname( __FILE__ ) ),
            'supports'              => $supports,
            'has_archive'           => false,
            'hierarchical'          => true
        ) );

        register_post_type(__('Definition','rldh-definitions-hyperlinks'),$args);
    }

    private function get_tooltip_link($url, $link, $title) {
        return apply_filters('rldh_tooltip_html', str_replace(array("rldh_REPLACE_URL","rldh_REPLACE_LINK","rldh_REPLACE_TITLE"),array($url, $link,$title),self::tooltip_html));
    }

    public function replace_definitions_with_links( $content ) {
        $count=0;
        $content_length = strlen($content);
        $unlikely_nr = $content_length+10;

        //find definitions in buffer
        $args = array(
            'post_type'        => __('Definition','rldh-definitions-hyperlinks'),
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'suppress_filters' => true 
        );

        $definitions = get_posts( apply_filters( 'rldh_definitions_query',$args ));
        if ( $definitions ) {
          foreach ( $definitions as $definition ) {
            //check if this post IS this definition, else skip to next definition
            if (get_the_ID()!=$definition->ID) {
                $start_looking_at = 0;
                $url = get_permalink($definition->ID);
                $name = apply_filters( 'the_title' , $definition->post_title);
                $excerpt = apply_filters( 'the_excerpt' , $definition->post_excerpt);
                //remove tags from excerpt
                $excerpt = strip_tags($excerpt);
                //remove quotes
                $excerpt = str_replace('"', "", $excerpt);
                $excerpt = str_replace("'", "", $excerpt);
                if (strlen($excerpt)>0) {$excerpt = $excerpt." ";}
                $excerpt .=__('Click for more information','rldh-definitions-hyperlinks');

                $excerpt_arr[] = $excerpt;
                $placeholder   = $count.'_rldh_excerpt';
                $placeholder_arr[] = $placeholder;

                $name_lc   = strtolower($name);
                $name_uc   = strtoupper($name);
                $name_ucf  = ucfirst($name);

                $link_lc   = $this :: get_tooltip_link($url,$name_lc,$placeholder);
                $link_uc   = $this :: get_tooltip_link($url,$name_uc,$placeholder);
                $link_ucf  = $this :: get_tooltip_link($url,$name_ucf,$placeholder);
                
                //continue until end of string, or break
                // use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
                while ($start_looking_at<$content_length) {
                    $poslc  = strpos($content, $name_lc, $start_looking_at);
                    $posuc  = strpos($content, $name_uc, $start_looking_at);
                    $posucf = strpos($content, $name_ucf, $start_looking_at);

                    //get the position of the first occurring string
                    if ($poslc  === false) {$poslc = $unlikely_nr;}
                    if ($posuc  === false) {$posuc = $unlikely_nr;}
                    if ($posucf === false) {$posucf= $unlikely_nr;}
                    $start_looking_at = min(array($poslc,$posuc,$posucf));

                    //if equal to unlikely nr, no occurrence was found, stop
                    if ($start_looking_at!=$unlikely_nr) {
                        //check if firstposition is within an image or hyperlink tag
                        $pos_close1 = strpos($content, ">",$start_looking_at);
                        $pos_open1 = strpos($content,"<", $start_looking_at);

                        $pos_close2 = strpos($content, "</a",$start_looking_at);
                        $pos_open2 = strpos($content,"<a", $start_looking_at);
                        if (($pos_open2===false && $pos_close2!==false) || ($pos_close2<$pos_open2)) {
                            //found hyperlink
                            $start_looking_at = $pos_close2+1;
                        }
                        elseif (($pos_open1===false && $pos_close1!==false) || ($pos_close1<$pos_open1)) {
                            //text within brackets, ignore
                            $start_looking_at = $pos_close1+1;
                        } else {
                            //html free definition, replace, then exit while
                            //find out if we need to replace with lowercase, uppercase, or uppercasefirst string
                            if (($poslc != $unlikely_nr) && ($poslc==$start_looking_at)) {
                                $content = substr_replace($content,$link_lc,$poslc,strlen($name_lc));
                            }
                            elseif (($posuc != $unlikely_nr) && ($posuc==$start_looking_at)) {
                                $content = substr_replace($content,$link_uc,$posuc,strlen($name_uc));
                            }
                            elseif (($posucf != $unlikely_nr) && ($posucf==$start_looking_at)) {
                                $content = substr_replace($content,$link_ucf,$posucf,strlen($name_ucf));
                            }
                            break;
                        }
                    }
                }
                $count++;
              }
          }
        }

        if (isset($placeholder_arr)) {$content = str_replace($placeholder_arr,$excerpt_arr, $content);}
        return apply_filters( 'rldh_replace_definitions', $content );
    }

    public function add_shortcode($atts){
        extract(shortcode_atts(array('nrofcolumns'=>2, 'characterheadings'=>'yes'), $atts,'rldh-definitions-list'));
        if (!isset($nrofcolumns)) {
                $nrofcolumns=2;
        } elseif ($nrofcolumns>$this::max_nr_of_columns_in_shortcode_list) {
            $nrofcolumns = $this::max_nr_of_columns_in_shortcode_list;
        } else {
            switch ($nrofcolumns) {
            case 5:
                $nrofcolumns=4;
                break;
            case 6: case 7: case 8:
                $nrofcolumns=6;
                break;
            case 9: case 10: case 11:
                $nrofcolumns=12;
                break;
            }
        }

        $col = 12/$nrofcolumns;

        //return a list of definitions here
        $definitions_args = array(
            'post_type'        => __('Definition','rldh-definitions-hyperlinks'),
            'post_status'      => 'publish',
            'orderby'          => 'title',
            'order'            => 'ASC',
            'numberposts'      => -1,
            );
        
        $definitions = get_posts( apply_filters( 'rldh_definitions_shortcode_list_query',$definitions_args ));
        $html="";
        $html_column = "";
        $total_nr_of_definitions = count($definitions);
        $i = $total_nr_of_definitions/$nrofcolumns;
        $definitions_per_column = round($i);
        $old_char = "";
        $counter=1;
        if ( $definitions ) {
            foreach ( $definitions as $definition ) {
                $url = get_permalink($definition->ID);
                $name = apply_filters( 'the_title' , $definition->post_title);
                $new_char = strtoupper(substr($name,0,1));
                if ($new_char!=$old_char) {
                    $html_column.="<div class='rldh-firstletter'><h2>".$new_char."</h2></div>";
                    $old_char = $new_char;
                }
                $html_column .= "<div><a href='".$url."'>".$name."</a></div>";
                //wrap in a column if $defitions per column is at multiple value of $counter, 
                //or when it is the last column
                if ((($counter % $definitions_per_column == 0) && $counter!=0) || (($total_nr_of_definitions-$counter)<$definitions_per_column)) {
                    $html.= "<div class='rldh-columnheader col-md-".$col."'>".$html_column."</div>";
                    $html_column = "";
                }
                $counter++;
            }
        }
        $html = "<div class='col-md-12'>".$html."</div>";
        return apply_filters( 'rldh_insert_shortcode', $html);
    }

}
$rldh_definition = new rldh_definition();

class rldl_definitions_widget extends WP_Widget {
    public function __construct() {
        $widget_ops = array( 'description' => __( "A list of your most used definitions.", 'rldh-definitions-hyperlinks') );
        parent::__construct('rldl_definitions_widget', __('Definitions list'), $widget_ops);
    }

    public function widget( $args, $instance ) {
        if ( !empty($instance['title']) ) {
            $title = $instance['title'];
        } else {
                $title = __('Definitions', 'rldh-definitions-hyperlinks');
        }
        if (!empty($instance['nr_of_definitions'])) {
            $nr_of_definitions = $instance['nr_of_definitions'];
        }
        else {
            $nr_of_definitions = rldh_definition::default_nr_of_definitions;
        }
        
        $title = apply_filters( 'rldh_widget_title', $title, $instance, $this->id_base );

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        echo '<div class="rldh-definitions-list-wrapper">';
        //return a list of definitions here
        $definitions_args = array(
            'post_type'        => __('Definition','rldh-definitions-hyperlinks'),
            'post_status'      => 'publish',
            'orderby'          => 'rand',
            'numberposts'      => $nr_of_definitions,
            );
        echo "<ul class='rldh-definitions-list'>";
        $definitions = get_posts( apply_filters( 'rldh_definitions_widget_list_query',$definitions_args ));
        if ( $definitions ) {
            foreach ( $definitions as $definition ) {
                $url = get_permalink($definition->ID);
                $name = apply_filters( 'the_title' , $definition->post_title);
                echo "<li class='rldh-definitions-list-item'><a href='".$url."'>".$name."</a></li>";
            }
        }
        echo "</ul>";
        echo "</div>\n";
        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance['title'] = strip_tags(stripslashes($new_instance['title']));
        $instance['nr_of_definitions'] = stripslashes($new_instance['nr_of_definitions']);
        return $instance;
    }

    public function form( $instance ) {
?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
    <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
    <p><label for="<?php echo $this->get_field_id('nr_of_definitions'); ?>"><?php echo __('Number of definitions listed','rldh-definitions-hyperlinks'); ?></label>
    <input type="text" size="3" id="<?php echo $this->get_field_id('nr_of_definitions'); ?>" name="<?php echo $this->get_field_name('nr_of_definitions'); ?>" value="<?php if (isset ( $instance['nr_of_definitions'])) {echo esc_attr( $instance['nr_of_definitions'] );} else {echo rldh_definition::default_nr_of_definitions;} ?>" />
    </p>
    <?php
    }
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("rldl_definitions_widget");'));

//unset($rldh_definition);