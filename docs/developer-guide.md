# TracePilot for WordPress - Developer Guide

## Introduction

This guide is intended for developers who want to integrate with TracePilot for WordPress, either to log custom events or to extend the plugin's functionality.

## Logging Custom Events

### Basic Usage

To log a custom event, use the `TracePilot_Helpers::log_activity()` method:

```php
// Make sure the helper class is initialized
TracePilot_Helpers::init();

// Log a simple activity
TracePilot_Helpers::log_activity(
    'custom_action_name',    // Action identifier
    'Description of action', // Human-readable description
    'info'                   // Severity: 'info', 'warning', or 'error'
);
````

### Advanced Usage

For more detailed logging, you can include additional context:

```php
// Log activity with context
TracePilot_Helpers::log_activity(
    'product_purchased',                  // Action identifier
    'User purchased Product X',           // Human-readable description
    'info',                               // Severity
    array(
        'object_type' => 'product',
        'object_id'   => 123,
        'object_name' => 'Product X',
        'context'     => array(
            'price'   => 49.99,
            'quantity' => 2,
            'total'   => 99.98
        )
    )
);
```

### Available Severity Levels

* `info`: Normal activities, informational only
* `warning`: Activities that might require attention
* `error`: Critical activities that indicate problems

### Logging User Actions

When logging actions performed by users other than the current user:

```php
// Log activity for a specific user
TracePilot_Helpers::log_activity(
    'custom_user_action',
    'User performed a custom action',
    'info',
    array(
        'user_id' => 42
    )
);
```

## Hooks and Filters

### Actions

#### `tracepilot_before_log_activity`

Fired before an activity is logged.

```php
add_action('tracepilot_before_log_activity', function($action, $description, $severity, $args) {
    // Do something before logging
}, 10, 4);
```

#### `tracepilot_after_log_activity`

Fired after an activity has been logged.

```php
add_action('tracepilot_after_log_activity', function($log_id, $action, $description, $severity, $args) {
    // Do something after logging
}, 10, 5);
```

#### `tracepilot_log_deleted`

Fired when a log entry is deleted.

```php
add_action('tracepilot_log_deleted', function($log_id) {
    // Do something when a log is deleted
}, 10, 1);
```

### Filters

#### `tracepilot_log_data`

Filter the data before it's inserted into the database.

```php
add_filter('tracepilot_log_data', function($log_data, $action, $description, $severity, $args) {
    // Modify $log_data before it's saved
    return $log_data;
}, 10, 5);
```

#### `tracepilot_should_log_activity`

Determine whether an activity should be logged.

```php
add_filter('tracepilot_should_log_activity', function($should_log, $action, $description, $severity, $args) {
    if ($action === 'some_action_to_ignore') {
        return false;
    }
    return $should_log;
}, 10, 5);
```

#### `tracepilot_log_retention_days`

Filter the number of days to keep logs.

```php
add_filter('tracepilot_log_retention_days', function($days) {
    return 60; // Keep logs for 60 days
}, 10, 1);
```

## Database Schema

The plugin stores logs in a custom table with the following structure:

```sql
CREATE TABLE {$wpdb->prefix}tracepilot_activity_log (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    time datetime NOT NULL,
    user_id bigint(20) unsigned DEFAULT NULL,
    username varchar(60) DEFAULT NULL,
    user_role varchar(255) DEFAULT NULL,
    action varchar(255) NOT NULL,
    description text NOT NULL,
    severity varchar(20) NOT NULL DEFAULT 'info',
    object_type varchar(255) DEFAULT NULL,
    object_id varchar(255) DEFAULT NULL,
    object_name varchar(255) DEFAULT NULL,
    ip varchar(45) DEFAULT NULL,
    browser varchar(255) DEFAULT NULL,
    context longtext DEFAULT NULL,
    PRIMARY KEY (id),
    KEY time (time),
    KEY user_id (user_id),
    KEY action (action),
    KEY severity (severity)
);
```

## Extending the Plugin

### Adding Custom Widgets

To add a custom widget to the dashboard:

```php
add_filter('tracepilot_dashboard_widgets', function($widgets) {
    $widgets['my_custom_widget'] = array(
        'title' => 'My Custom Widget',
        'callback' => 'my_custom_widget_callback',
        'icon' => 'bar-chart-2',
        'position' => 'column-1'
    );
    return $widgets;
});

function my_custom_widget_callback() {
    echo '<div>Custom widget content</div>';
}
```

### Adding Export Formats

```php
add_filter('tracepilot_export_formats', function($formats) {
    $formats['custom_format'] = array(
        'label' => 'My Custom Format',
        'callback' => 'my_custom_export_callback',
        'icon' => 'file-text'
    );
    return $formats;
});

function my_custom_export_callback($logs, $args) {
    $output = ''; // Generate your formatted output
    return $output;
}
```

### Adding Custom Notification Channels

```php
add_filter('tracepilot_notification_channels', function($channels) {
    $channels['custom_channel'] = array(
        'label' => 'My Custom Channel',
        'callback' => 'my_custom_notification_callback',
        'icon' => 'bell'
    );
    return $channels;
});

function my_custom_notification_callback($event, $log_data) {
    return true; // Send notification through your custom channel
}
```

## Best Practices

### Performance Considerations

* Log only significant events
* Use appropriate severity levels
* Consider indexes for custom queries
* Use `context` for detailed data

### Security Considerations

* Never log sensitive data (passwords, API keys)
* Ensure GDPR compliance
* Sanitize data before logging
* Use proper capability checks

### Compatibility

* Prefix actions with your plugin/theme slug
* Check for `TracePilot_Helpers` class existence
* Use provided hooks and filters

## Example Implementations

### WooCommerce Integration

```php
add_action('woocommerce_order_status_completed', 'log_woocommerce_purchase');

function log_woocommerce_purchase($order_id) {
    if (!class_exists('TracePilot_Helpers')) {
        return;
    }

    TracePilot_Helpers::init();

    $order = wc_get_order($order_id);
    $items = $order->get_items();
    $products = array();

    foreach ($items as $item) {
        $products[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
    }

    TracePilot_Helpers::log_activity(
        'woocommerce_purchase',
        sprintf('Order #%s completed for %s', $order->get_order_number(), $order->get_formatted_billing_full_name()),
        'info',
        array(
            'object_type' => 'order',
            'object_id' => $order_id,
            'object_name' => 'Order #' . $order->get_order_number(),
            'context' => array(
                'total' => $order->get_total(),
                'products' => $products,
                'payment_method' => $order->get_payment_method_title()
            )
        )
    );
}
```

### Custom Post Type Integration

```php
add_action('save_post_my_custom_post', 'log_custom_post_save', 10, 3);

function log_custom_post_save($post_id, $post, $update) {
    if (!class_exists('TracePilot_Helpers') || wp_is_post_revision($post_id)) {
        return;
    }

    TracePilot_Helpers::init();

    $action = $update ? 'updated' : 'created';

    TracePilot_Helpers::log_activity(
        'custom_post_' . $action,
        sprintf('Custom post "%s" was %s', get_the_title($post_id), $action),
        'info',
        array(
            'object_type' => 'post',
            'object_id' => $post_id,
            'object_name' => get_the_title($post_id)
        )
    );
}
```

## Conclusion

TracePilot for WordPress provides a robust framework for tracking activities in WordPress. Use the provided API and hooks to integrate your plugins and themes for comprehensive logging.
