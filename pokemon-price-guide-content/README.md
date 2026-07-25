# Pokémon TCG Card Content System Documentation

## Overview

The Pokémon TCG Card Content System is a WordPress plugin that adds rich text content capabilities to Pokémon TCG card pages. It allows administrators to create and manage custom content for individual cards using the Gutenberg editor, while maintaining the existing URL structure of the card pages.

## Features

- Shadow post type system that doesn't affect URL structure
- Mapping table to connect cards to content
- Admin interface for managing card content
- Gutenberg editor integration for rich text editing
- Search and filter functionality for finding cards
- Card preview functionality
- Responsive design for all screen sizes

## Installation

1. Upload the `pokemon-price-guide-content` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Card Content' in the admin menu to start managing card content

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Primetime Price Guide plugin installed and activated

## Repository Files

- `primetime-tcg-card-content.php` is the runtime WordPress plugin file.
- `css/admin.css` and `js/admin.js` are runtime assets loaded only on the plugin's Card Content Manager administration page.
- `card_content_template_example.html` is a documentation-only example for manual content authoring. Runtime code and the administration interface do not load it.
- `README.md` is project documentation.

There is currently no external content-generation script or build process for this plugin. No standalone tools directory is required.

## Usage

### Managing Card Content

1. Navigate to 'Card Content > Manage Content' in the WordPress admin
2. You'll see two sections:
   - Cards with Custom Content: Shows cards that already have custom content
   - Cards without Custom Content: Shows cards that don't have custom content yet
3. To create content for a card, click the 'Create Content' button next to the card
4. To edit existing content, click the 'Edit Content' button next to the card
5. To view a card on the frontend, click the 'View Card' button

### Editing Card Content

1. When creating or editing card content, you'll be taken to the standard WordPress editor
2. The editor uses Gutenberg, allowing you to add various types of content:
   - Paragraphs
   - Headings
   - Lists
   - Images
   - Galleries
   - Videos
   - Tables
   - Custom blocks
3. Edit the content as needed and click 'Update' to save your changes
4. The custom content will appear below the standard card template on the frontend

### Searching and Filtering Cards

1. Use the search box to find specific cards by name or set
2. Use the filters to narrow down the list of cards:
   - Filter by Set: Show cards from a specific set
   - Filter by Type: Show only Pokémon, Trainer, or Energy cards
   - Filter by Status: Show cards with or without custom content
3. Click 'Apply Filters' to update the list based on your selections
4. Click 'Reset Filters' to clear all filters

## Technical Details

### Database Structure

The plugin creates a custom database table to map cards to content:

```
wp_ptp_card_content_map
- id (bigint): Primary key
- card_id (varchar): ID of the card in the Pokémon TCG API
- post_id (bigint): ID of the corresponding WordPress post
- created_at (datetime): When the mapping was created
- updated_at (datetime): When the mapping was last updated
```

### Custom Post Type

The plugin registers a custom post type for card content:

```php
register_post_type('card_content', array(
    'public'              => false,
    'publicly_queryable'  => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'query_var'           => false,
    'rewrite'             => false,
    'capability_type'     => 'post',
    'has_archive'         => false,
    'hierarchical'        => false,
    'menu_position'       => 5,
    'menu_icon'           => 'dashicons-tickets-alt',
    'supports'            => array(
        'title',
        'editor',
        'thumbnail',
        'revisions',
    ),
    'show_in_rest'        => true, // Enable Gutenberg editor
));
```

### Content Display

The plugin hooks into the `the_content` filter to append custom content to card pages:

```php
add_filter('the_content', 'filter_card_content', 20);

function filter_card_content($content) {
    global $wpdb;
    
    // Check if we're inside the main loop in a single Post with pgpokeid
    if (get_query_var('pgpokeid') != '' && in_the_loop()) {
        $card_id = get_query_var('pgpokeid');
        
        // Check if we have custom content for this card
        $content_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}ptp_card_content_map WHERE card_id = %s",
            $card_id
        ));
        
        if ($content_post_id) {
            // Get the custom content
            $custom_content = get_post_field('post_content', $content_post_id);
            
            // Apply filters to render blocks properly
            $custom_content = apply_filters('the_content', $custom_content);
            
            // Append custom content to the template content
            $content .= '<div class="card-custom-content"><h2>Additional Information</h2>' . $custom_content . '</div>';
        }
    }
    
    return $content;
}
```

## Performance Considerations

The plugin is designed to minimize performance impact:

1. **Shadow Post Type**: Only creates posts for cards that have custom content
2. **Efficient Queries**: Uses direct database queries with proper indexing
3. **Pagination**: Admin interface uses pagination to handle large numbers of cards
4. **Caching**: Works with existing caching systems

## Future Enhancements

Planned for future versions:

1. **Bulk Content Management**: Add ability to create or edit content for multiple cards at once
2. **Content Templates**: Create reusable templates for card content
3. **Import/Export**: Import or export card content in bulk
4. **Advanced Filtering**: More advanced filtering options in the admin interface
5. **Statistics Dashboard**: Dashboard with statistics about card content

## Troubleshooting

### Common Issues

1. **Content not appearing on card pages**:
   - Verify that the card ID in the mapping table matches the ID used in the URL
   - Check that the content post is published and not in draft status
   - Ensure the Primetime Price Guide plugin is active

2. **Admin interface not loading properly**:
   - Check for JavaScript errors in the browser console
   - Verify that the plugin's CSS and JavaScript files are being loaded
   - Try disabling other plugins to check for conflicts

3. **Database errors**:
   - Verify that the mapping table was created successfully
   - Check database permissions

### Getting Help

If you encounter issues not covered in this documentation, please:

1. Check the plugin's GitHub repository for known issues
2. Submit a new issue with detailed information about the problem
3. Contact the plugin developer for support

## Changelog

### Version 1.0.0 (May 5, 2025)
- Initial release
- Shadow post type system for card content
- Admin interface for managing card content
- Gutenberg editor integration
- Search and filter functionality
- Card preview functionality
