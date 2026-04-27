<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('app:status', fn() => $this->info('OK'));
