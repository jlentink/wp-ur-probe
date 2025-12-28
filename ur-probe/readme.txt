=== UR-Probe ===
Contributors: yourname
Tags: health check, monitoring, uptime, probe, mysql
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple health check probe that verifies MySQL connection and WordPress status.

== Description ==

UR-Probe provides a lightweight health check endpoint for your WordPress site. It verifies that:

* MySQL database connection is working
* WordPress core is properly loaded
* Essential database queries can be executed

The probe outputs a simple response:
* **OK** - Everything is working correctly (HTTP 200)
* **ERR** - Something is wrong (HTTP 503)

This is ideal for uptime monitoring services, load balancers, and container orchestration health checks.

== Installation ==

1. Upload the `ur-probe` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > UR-Probe to configure the probe path
4. Visit your configured URL (default: `https://yoursite.com/ur-probe/`)

== Configuration ==

Navigate to **Settings > UR-Probe** in your WordPress admin panel.

* **Probe Path**: Set the URL path where the health check will be accessible (default: `ur-probe`)

After changing the path, the plugin automatically flushes rewrite rules.

== Frequently Asked Questions ==

= What does the probe check? =

The probe verifies:
1. MySQL database connection is active
2. A simple database query executes successfully
3. WordPress core functions are available
4. The options table is accessible

= Can I use this with external monitoring services? =

Yes! Services like UptimeRobot, Pingdom, or any HTTP monitoring tool can poll your probe URL and alert you if it returns ERR or becomes unreachable.

= Does this expose any sensitive information? =

No. The probe only outputs "OK" or "ERR" - no system details, versions, or configuration data is exposed.

== Changelog ==

= 1.0.0 =
* Initial release
* Configurable probe path
* MySQL connection check
* WordPress health verification
