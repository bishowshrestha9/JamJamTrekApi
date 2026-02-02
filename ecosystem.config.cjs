module.exports = {
  apps: [
    {
      name: 'jamjam-trek-api',
      script: 'php',
      args: 'artisan serve --host=0.0.0.0 --port=8001',
      cwd: '/var/www/JamJamTrekApi',
      interpreter: 'none',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      env: {
        APP_ENV: 'production',
        NODE_ENV: 'production'
      },
      error_file: './storage/logs/pm2-error.log',
      out_file: './storage/logs/pm2-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      merge_logs: true,
      time: true
    },
    {
      name: 'jamjam-queue-worker',
      script: 'php',
      args: 'artisan queue:work --sleep=3 --tries=3 --max-time=3600',
      cwd: '/var/www/JamJamTrekApi',
      interpreter: 'none',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      env: {
        APP_ENV: 'production',
        NODE_ENV: 'production'
      },
      error_file: './storage/logs/queue-error.log',
      out_file: './storage/logs/queue-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      merge_logs: true,
      time: true
    }
  ]
};
