<?php
/**
 * @file
 * Hook implementations and functions for the Bedrock theme.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function bedrock_preprocess_page(&$variables) {
  // @see bedrock.js
  $variables['classes'][] = 'no-jscript';

  $path = current_path();
  $args = arg();
  // Various CSS classes like page-node-123 or page-node-edit.
  if (substr($path, 0, 5) == 'node/' && $node = menu_get_object()) {
    if (count($args) == 2) {
      $variables['classes'][] = 'page-node-' . $node->nid;
    }
    else {
      $variables['classes'][] = backdrop_clean_css_identifier('page-node-' . $args[2]);
    }
  }
  // CSS class for node preview page.
  elseif (substr($path, 0, 13) == 'node/preview/') {
    $variables['classes'][] = 'node-preview-page';
  }
  // CSS classes to term listing pages.
  elseif (substr($path, 0, 14) == 'taxonomy/term/') {
    if (count($args) == 3) {
      $term = taxonomy_term_load($args[2]);
      if ($term) {
        $variables['classes'][] = backdrop_clean_css_identifier('page-term-vocab-' . $term->vocabulary);
        $variables['classes'][] = 'page-term-' . $term->tid;
      }
    }
  }
  // Admin pages.
  elseif (substr($path, 0, 6) == 'admin/') {
    $variables['classes'][] = 'admin-page';
  }
  // For example standalone layouts or views pages.
  else {
    $variables['classes'][] = backdrop_clean_css_identifier('path-' . $path);
  }

  // Icon API.
  backdrop_add_icons(array(
    'arrows-out-cardinal',
    'question-fill',
    'x',
  ));

  // Allow some special styles, if bedrock is the default.
  // For styles that would get into the way for subthemes.
  if (config_get('system.core', 'theme_default') == 'bedrock') {
    $variables['classes'][] = 'bedrock-no-subtheme';
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function bedrock_preprocess_layout(&$variables) {
  if (isset($variables['layout_info']['flexible'])) {
    // Add CSS class to layout based on flexible template machine name.
    $variables['classes'][] = backdrop_clean_css_identifier('layout-' . $variables['layout_info']['name']);
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function bedrock_preprocess_node(&$variables) {
  if ($variables['status'] == NODE_NOT_PUBLISHED) {
    $scheduled = $variables['scheduled'];
    $icon = '';
    if (function_exists('icon')) {
      $icon_name = ($scheduled) ? 'clock-clockwise': 'info';
      $icon = icon($icon_name, array(
        'attributes' => array(
          'width' => '28',
          'height' => '28',
          'class' => array('icon-bedrock'),
        ),
      ));
    }
    $name = node_type_get_name($variables['type']);
    if ($scheduled) {
      $message = $icon . ' ' . t('This @type will be published on @date.', array(
        '@type' => $name,
        '@date' => format_date($scheduled),
      ));
    }
    else {
      $message = $icon . ' ' . t('This @type is unpublished.', array('@type' => $name));
    }
    $variables['title_suffix']['unpublished_indicator'] = array(
      '#type' => 'markup',
      '#markup' => '<div class="unpublished-indicator">' . $message . '</div>',
    );
  }
}
/**
 * Implements hook_css_alter().
 */
function bedrock_css_alter(&$css) {
  unset($css['core/modules/node/css/node.preview.css']);
  unset($css['core/modules/system/css/system.theme.css']);
}

/**
 * Implements comment_view_alter().
 */
function bedrock_comment_view_alter(&$build) {
  $comment = $build['#comment'];
  $node = $build['#node'];
  $node_type = node_type_get_type($node->type);
  // @see CommentStorageController::view().
  if (empty($comment->in_preview)) {
    $prefix = '';
    $is_threaded = isset($comment->divs) && $node_type->settings['comment_mode'] == COMMENT_MODE_THREADED;

    // Add 'new' anchor if needed.
    if (!empty($comment->first_new)) {
      $prefix .= "<a id=\"new\"></a>\n";
    }

    // Add indentation div or close open divs as needed.
    if ($is_threaded) {
      // Custom: Phosphor icon.
      global $language;
      if ($language->direction == LANGUAGE_RTL) {
        $icon_name = 'arrow-elbow-down-left';
      }
      else {
        $icon_name = 'arrow-elbow-down-right';
      }
      $options = array('attributes' => array('class' => array('indent-icon')));
      $indent_icon = icon($icon_name, $options);
      $prefix .= $comment->divs <= 0 ? str_repeat('</div>', abs($comment->divs)) : "\n" . '<div class="indented">' . $indent_icon;
    }

    // Add anchor for each comment.
    $prefix .= "<a id=\"comment-$comment->cid\"></a>\n";
    $build['#prefix'] = $prefix;

    // Close all open divs.
    if ($is_threaded && !empty($comment->divs_final)) {
      $build['#suffix'] = str_repeat('</div>', $comment->divs_final);
    }
  }

  // It makes no sense to display "Login to post comments" on every single
  // comment.
  if (!empty($build['links']['comment']['#links'])) {
    unset($build['links']['comment']['#links']['comment-forbidden']);
  }
}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Preprocess comment.tpl.php
 */
function bedrock_preprocess_comment(&$variables) {
  $comment = $variables['comment'];
  $uri = $comment->uri();
  $uri['options'] += array('attributes' => array('class' => array('permalink'), 'rel' => 'bookmark'));
  $icon = icon('link', array(
    'alt' => t('Permalink'),
  ));
  $url = url($uri['path'], $uri['options']);
  $variables['permalink'] = '<a class="permalink" href="' . $url . '">' . $icon . '</a>';
}

/**
 * Overrides theme_breadcrumb().
 */
function bedrock_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  $output = '';
  if (!empty($breadcrumb)) {
    $output .= '<nav class="breadcrumb" aria-label="' . t('Website Orientation') . '">';
    $output .= '<ol><li>' . implode('</li><li>', $breadcrumb) . '</li></ol>';
    $output .= '</nav>';
  }
  return $output;
}

/**
 * Overrides theme_tablesort_indicator().
 */
function bedrock_tablesort_indicator($variables) {
  if ($variables['style'] == 'asc') {
    $icon = 'caret-down-fill';
    $alt = t('sort ascending');
  }
  else {
    $icon = 'caret-up-fill';
    $alt = t('sort descending');
  }

  $options = array(
    'alt' => $alt,
    'attributes' => array(
      'width' => 16,
      'height' => 16,
    ),
  );

  return icon($icon, $options);
}
