<?php
/**
 * Dynamic Tags
 *
 * @package fc_corporativa
 */

 /* ------------- Blog Breadcrumbs */
 function display_tag(
   $tag_group,
   $selected_tag,
   $tag_content,
   $css_classes
 ) {

  global $post;
  while( have_rows($tag_group) ): the_row();
    $selected_tag = get_sub_field($selected_tag);
    $tag_content = get_sub_field($tag_content);
  endwhile;
  $output ='';
  $output .= '<'. $selected_tag;
  $output .= ' class="' . $css_classes . '"';
  $output .= '>';
  $output .= $tag_content;
  $output .= '</'. $selected_tag .'>';
   echo $output;
}

function display_tag_cpt(
  $selected_tag,
  $css_classes
) {

 global $post;
 $selected_tag = get_field($selected_tag);
 $tag_content = get_the_title($post->ID);

 $output = '<'. $selected_tag;
 $output .= ' class="' . $css_classes . '"';
 $output .= '>';
 $output .= $tag_content;
 $output .= '</'. $selected_tag .'>';
  echo $output;
}

function display_tag_cpt_link(
  $selected_tag,
  $css_classes
) {

 global $post;
 $selected_tag = get_field($selected_tag);
 $tag_content = get_the_title($post->ID);
 $cpt_link = get_the_permalink($post->ID);

 $output = '<'. $selected_tag;
 $output .= ' class="' . $css_classes . '"';
 $output .= '>';
 $output .= '<a href="' . $cpt_link . '">';
 $output .= $tag_content;
 $output .= '</a>';
 $output .= '</'. $selected_tag .'>';
  echo $output;
}

function display_tag_id(
  $tag_group,
  $selected_tag,
  $tag_content,
  $page_id,
  $css_classes
) {

 global $post;
 while( have_rows($tag_group,$page_id) ): the_row();
   $selected_tag = get_sub_field($selected_tag,$page_id);
   $tag_content = get_sub_field($tag_content,$page_id);
 endwhile;
 $output ='';
 $output .= '<'. $selected_tag;
 $output .= ' class="' . $css_classes . '"';
 $output .= '>';
 $output .= $tag_content;
 $output .= '</'. $selected_tag .'>';
  echo $output;
}
