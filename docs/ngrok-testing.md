# Testing PWA with ngrok (HTTPS on mobile)

Service workers require HTTPS. When testing on a phone against a local wp-env, use ngrok to tunnel HTTPS traffic.

## Prerequisites

- ngrok installed (`brew install ngrok`) with auth token configured
- wp-env running (`npm run wp-env` — default port 5880)

## Setup

### 1. Start the ngrok tunnel

```bash
ngrok http 5880 --host-header=localhost:5880
```

Note the generated HTTPS URL (e.g. `https://xxxx-xx-xx-xx-xx.ngrok-free.app`).

### 2. Modify wp-config.php inside the Docker container

wp-env hardcodes `WP_SITEURL` and `WP_HOME` via `define()`. Database option updates and `option_*` filters do not override `define()` constants. The constants must be made dynamic.

Replace the existing `WP_SITEURL` and `WP_HOME` lines:

```bash
npx wp-env run cli sed -i "s|define( 'WP_SITEURL'.*|define( 'WP_SITEURL', isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) ? 'https://' . \$_SERVER['HTTP_X_FORWARDED_HOST'] : 'http://localhost:5880' );|" /var/www/html/wp-config.php

npx wp-env run cli sed -i "s|define( 'WP_HOME'.*|define( 'WP_HOME', isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) ? 'https://' . \$_SERVER['HTTP_X_FORWARDED_HOST'] : 'http://localhost:5880' );|" /var/www/html/wp-config.php
```

### 3. Create the mu-plugin for reverse proxy support

ngrok sets `HTTP_X_FORWARDED_PROTO` and `HTTP_X_FORWARDED_HOST` headers. WordPress needs `$_SERVER['HTTPS']` and correct port/host values.

```bash
npx wp-env run cli bash -c 'mkdir -p /var/www/html/wp-content/mu-plugins && cat > /var/www/html/wp-content/mu-plugins/ngrok-proxy.php << "MUEOF"
<?php
if ( isset( \$_SERVER["HTTP_X_FORWARDED_PROTO"] ) && \$_SERVER["HTTP_X_FORWARDED_PROTO"] === "https" ) {
    \$_SERVER["HTTPS"]       = "on";
    \$_SERVER["SERVER_PORT"] = 443;
    \$ngrok_url = "https://" . \$_SERVER["HTTP_X_FORWARDED_HOST"];
    add_filter( "option_siteurl", function () use ( \$ngrok_url ) { return \$ngrok_url; } );
    add_filter( "option_home",    function () use ( \$ngrok_url ) { return \$ngrok_url; } );
}
MUEOF'
```

### 4. Flush rewrite rules

```bash
npx wp-env run cli wp rewrite flush
```

### 5. Test

```bash
curl -sI "https://xxxx-xx-xx-xx-xx.ngrok-free.app/card/your-slug/"
# Should return HTTP/2 200
```

Visit the ngrok URL on your phone. The ngrok free tier shows an interstitial — tap through it once.

## Teardown

### 1. Kill ngrok

```bash
pkill ngrok
```

### 2. Restore wp-config.php

```bash
npx wp-env run cli sed -i "s|define( 'WP_SITEURL'.*|define( 'WP_SITEURL', 'http://localhost:5880' );|" /var/www/html/wp-config.php

npx wp-env run cli sed -i "s|define( 'WP_HOME'.*|define( 'WP_HOME', 'http://localhost:5880' );|" /var/www/html/wp-config.php
```

### 3. Remove the mu-plugin

```bash
npx wp-env run cli rm /var/www/html/wp-content/mu-plugins/ngrok-proxy.php
```

### Alternative: restart wp-env

Restarting wp-env regenerates wp-config.php from scratch, which also reverts the changes:

```bash
npm run stop-env && npm run wp-env
```

This loses the mu-plugin automatically (container filesystem is ephemeral for mu-plugins not mapped via `.wp-env.json`).

## Notes

- ngrok free tier URLs change each time you restart — update your phone's URL accordingly
- The ngrok interstitial page sets a cookie; subsequent requests pass through directly
- Safari Web Inspector on iOS: connect via USB, enable Web Inspector in phone Settings > Safari > Advanced, then open Safari on Mac > Develop > [device name]
- Service worker registration only appears in Safari Web Inspector if you connect BEFORE navigating to the page (or run registration manually from Console)
