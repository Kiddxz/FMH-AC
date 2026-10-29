# Extra 67: trust the hosting proxy

`bootstrap/app.php`: `$middleware->trustProxies(at: '*');` so links stay https:// behind the Hostinger / Railway proxy and the real visitor IP is logged.
