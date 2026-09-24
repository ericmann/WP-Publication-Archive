# Hooks

Every hook the plugin fires is fired only through `Hooks` (P4); every hook
name is a `Keys` constant (P1). The `Hooks::` column is `—` until the task
that adds the corresponding static method fills it in. `Since` is the
3.0.1-era version taken from the `@since`/`@uses` docblocks at `e913681`, or
`≤ 3.0.1` when none is given.

## Exposed filters

| Hook | `Hooks::` method | Arguments | Since |
|---|---|---|---|
| `wppa_open_url` | — | `string $url` | ≤ 3.0.1 |
| `wppa_download_url` | — | `string $url` | ≤ 3.0.1 |
| `wppa_mask_url` | — | `bool $mask` | ≤ 3.0.1 |
| `wppa_publication_icon` | — | `string $image_url, string $doctype` | ≤ 3.0.1 |
| `wppa_list_limit` | — | `int $limit` | ≤ 3.0.1 |
| `wpa-pubs_per_page` | — | `int $limit` (deprecated, still applied first) | ≤ 3.0.1 |
| `wppa_list_template` | — | `string $template_name` | ≤ 3.0.1 |
| `wppa_dropdown_template` | — | `string $template_name` | ≤ 3.0.1 |
| `wppa_widget_template` | — | `string $template_name` | ≤ 3.0.1 |
| `wppa_single_template` | — | `string $template_name` | ≤ 3.0.1 |
| `wppa_archive_template` | — | `string $template_name` | ≤ 3.0.1 |
| `wppa_publication_list_container` | — | `array $wppa_container` | ≤ 3.0.1 |
| `wpa-title` | — | `string $title, int $post_id` | ≤ 3.0.1 |
| `wpa-upload_image` | — | `string $thumb, int $post_id` | ≤ 3.0.1 |
| `wpa-authors` | — | `string $authors, int $post_id` | ≤ 3.0.1 |
| `wpa-summary` | — | `string $summary, int $post_id` | ≤ 3.0.1 |
| `wpa-keywords` | — | `string $keywords, int $post_id` | ≤ 3.0.1 |
| `wpa-categories` | — | `string $categories, int $post_id` | ≤ 3.0.1 |
| `wpa-summary-length` | — | `int $length` | ≤ 3.0.1 |
| `wpa-widget-summary-length` | — | `int $length` | ≤ 3.0.1 |
| `wp_pubarch_open_in_blank` | — | `bool $open_in_blank` | ≤ 3.0.1 |
| `wp-publication-archive-enabled` | `filter_enabled` | `bool $enabled` (added in 3.1.0, template) | 3.1.0 |

## Exposed action

| Hook | `Hooks::` method | Arguments | Since |
|---|---|---|---|
| `wppa_booted` | `booted` | `\WPPA\Plugin $plugin` (added in 3.1.0, template) | 3.1.0 |

## Core hooks the plugin applies

These are WordPress core filters the plugin calls with `apply_filters()`
(not hooks the plugin exposes as its own API), still confined to `Hooks`.

| Hook | `Hooks::` method | Arguments | Since |
|---|---|---|---|
| `widget_title` | — | `string $title, array $instance, string $id_base` | ≤ 3.0.1 |
| `list_cats` | — | `string $option_label` | ≤ 3.0.1 |
| `wp_dropdown_cats` | — | `string $output` | ≤ 3.0.1 |
| `widget_categories_args` | — | `array $cat_args` | ≤ 3.0.1 |
| `widget_categories_dropdown_args` | — | `array $cat_args` | ≤ 3.0.1 |
| `wp_list_categories` | — | `string $output, array $args` | ≤ 3.0.1 |
| `content_save_pre` | — | `string $content` | ≤ 3.0.1 |

## Consumed hooks

Hooks core or other plugins fire that the plugin listens to.

| Hook | Arguments | Since |
|---|---|---|
| `init` | none | ≤ 3.0.1 |
| `cli_init` | none | 3.1.0 (template) |
| `wp_enqueue_scripts` | none | ≤ 3.0.1 |
| `admin_enqueue_scripts` | `string $hook_suffix` | 3.1.0 |
| `rest_api_init` | none | 3.1.0 |
| `save_post` | `int $post_id` | ≤ 3.0.1 |
| `template_redirect` | none | ≤ 3.0.1 (delivery moves to `Delivery::handle()` in 3.1.0) |
| `query_vars` | `array $vars` | ≤ 3.0.1 |
| `excerpt_length` | `int $length` | ≤ 3.0.1 |
| `widgets_init` | none | ≤ 3.0.1 |
| `template_include` | `string $template` | ≤ 3.0.1 |
| `add_meta_boxes_publication` | `\WP_Post $post` | 3.1.0 |

### Consumed from the DAM

Added by P0-05; see §6.9.

| Hook | Arguments | Since |
|---|---|---|
| `vip_dam_indexed_attachment_ids` | `array $ids` | DAM |

## Removed in 3.1.0

- The `posts_where_request` callback (D4).
- The `posts_join_request` and `posts_distinct_request` callbacks that 3.0.1
  added and removed around a single query (D4); 3.1.0 does not register
  them at all.
- The `admin_notices` callback that warned when `allow_url_fopen` is off
  (D11).
