<?php

use App\Console\Commands\CheckIntegrations;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CheckIntegrations::class)->everyFiveMinutes()->withoutOverlapping();
