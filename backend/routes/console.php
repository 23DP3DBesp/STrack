<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('docbox:backup')->dailyAt('02:30');
