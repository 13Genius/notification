Laravel 4 notification system + Redis
============

Manage app notifications.

Write in config/app.php

```php

'providers' => array(
    ('Genius13\Notification\NotificationServiceProvider'),
),

'aliases' => array(
    'Notification' => 'Genius13\Notification\Facades\Notification',
),

```
