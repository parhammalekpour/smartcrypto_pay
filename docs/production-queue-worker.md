# Production queue worker for a generic Linux VPS

This project uses Laravel's database queue (`QUEUE_CONNECTION=database`). The scheduler dispatches blockchain jobs, but the queue worker must be kept alive by a process manager.

## 1) Install Supervisor

On Ubuntu/Debian:

```bash
sudo apt-get update
sudo apt-get install -y supervisor
```

## 2) Install the worker config

Copy the checked-in config to the Supervisor config directory:

```bash
sudo cp /var/www/smart-cryptopay/deployment/supervisor/smartcryptopay-queue.conf /etc/supervisor/conf.d/smartcryptopay-queue.conf
```

Update the paths and user if your deployment differs from the example:

- `/var/www/smart-cryptopay` -> your app directory
- `www-data` -> your web/deployment user

## 3) Reload Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## 4) Start/restart the worker

```bash
sudo supervisorctl start smartcryptopay-queue
sudo supervisorctl restart smartcryptopay-queue
```

## 5) Check logs

```bash
sudo supervisorctl status
sudo tail -f /var/log/supervisor/smartcryptopay-queue.out.log
sudo tail -f /var/log/supervisor/smartcryptopay-queue.err.log
```

## 6) Verify jobs are being consumed

The worker command is:

```bash
php artisan queue:work database --tries=3
```

After the worker is running, queue entries should be removed from the `jobs` table as they are processed. If the queue is idle, no jobs remain; if jobs pile up, the worker is not consuming them.

## 7) Important

The scheduler should not be treated as the queue worker. Scheduler dispatches jobs; Supervisor keeps the queue worker alive and restarts it when it crashes.
