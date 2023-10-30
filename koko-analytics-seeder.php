<?php

/**
Plugin Name: Koko Analytics - Seeder
Author: Danny
*/

if (class_exists(\WP_CLI::class)) {
    require __DIR__ . '/src/Command.php';
    WP_CLI::add_command('koko-analytics-seed', KokoAnalytics\Seeder\Command::class);
}
