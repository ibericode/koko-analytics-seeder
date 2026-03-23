<?php

/*
Plugin Name: Koko Analytics Seeder
Plugin URI: https://www.kokoanalytics.com/
Version: 1.0.0
Description: A plugin to seed Koko Analytics with test data for development and testing purposes.
Author: ibericode
Author URI: https://ibericode.com/
Author Email: support@kokoanalytics.com
Text Domain: koko-analytics
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


if (defined('WP_CLI') && WP_CLI) {
    require __DIR__ . '/src/Command.php';
    WP_CLI::add_command('koko-analytics-seed', KokoAnalytics\Seeder\Command::class);
}
