<?php

namespace KokoAnalytics\Seeder;

use WP_CLI;

class Command
{
    public function create_referrer_urls(int $n_referrers): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'koko_analytics_referrer_urls';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $n = $n_referrers - $count;

        $progress = \WP_CLI\Utils\make_progress_bar( 'Generating referrer URLs', $n );
        for ($i = 0; $i < $n; $i++) {
            $url = "referrer-$i.com";
            $wpdb->insert(
                $table,
                array(
                    'url' => $url,
                )
            );
            $progress->tick();
        }
        $progress->finish();

        return array_map(function ($row) {
            return $row->id;
        }, $wpdb->get_results("SELECT id FROM $table"));
    }


    public function create_paths(int $n): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'koko_analytics_paths';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $n = $n - $count;

        $progress = \WP_CLI\Utils\make_progress_bar( 'Generating paths', $n );
        for ($i = 0; $i < $n; $i++) {
            $random_string = bin2hex(random_bytes(4));
            $wpdb->insert(
                $table,
                array(
                    'path' => "/path-{$random_string}/",
                )
            );
            $progress->tick();
        }
        $progress->finish();

        return array_map(function ($row) {
            return $row->id;
        }, $wpdb->get_results("SELECT id FROM $table"));
    }


    public function __invoke(array $arguments = [], array $options = [])
    {
        global $wpdb;

        $years = $options['years'] ?? 3;
        $n_paths = $options['paths'] ?? 100;
        $n_referrers = $options['referrers'] ?? 100;

        // empty current data
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_site_stats");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_post_stats");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_referrer_stats");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_referrer_urls");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_paths");

        $paths = $this->create_paths($n_paths);
        $referrers = $this->create_referrer_urls($n_referrers);

        $progress = \WP_CLI\Utils\make_progress_bar( 'Generating stats', $years * 365 );
        for ($dt = new \DateTimeImmutable('now'); $dt > new \DateTimeImmutable("-{$years} years"); $dt = $dt->modify('-1 day')) {
            $date = $dt->format('Y-m-d');
            $pageviews = rand(10, 1000);
            $visitors = (int) (rand(2, 6) * $pageviews * 0.1);

            // simulate a huge peak in traffic every 180 days (this helps test chart scaling)
            if (rand(1, 180) === 1) {
                $pageviews = $pageviews * 10;
                $visitors  = $visitors * 10;
            }

            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}koko_analytics_site_stats(date, pageviews, visitors) VALUES(%s, %d, %d)", array($date, $pageviews, $visitors)));
            
            $this->seed_post_stats($paths, $date, $pageviews);
            $this->seed_referrers($referrers, $date, $pageviews);

            $progress->tick();
        }

        $progress->finish();
        WP_CLI::success("Finished seeding Koko Analytics with {$years} years of data, {$n_paths} paths and {$n_referrers} referrers.");
    }

    protected function seed_post_stats(array $paths, string $date, int $pageviews): void
    {
        global $wpdb;
        $values = array();
        $pageviews_per_post = $pageviews / count($paths) * 2;
        foreach ($paths as $path_id) {
            $post_pageviews = (int) ($pageviews_per_post * rand(5, 15) * 0.1);
            $post_visitors = (int) (rand(1, 3) * $post_pageviews * 0.1);
            array_push($values, $date, $path_id, 0, $post_pageviews, $post_visitors);

        }

        $placeholders = rtrim(str_repeat('(%s,%d,%d,%d,%d),', count($paths)), ',');
        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}koko_analytics_post_stats(date, path_id, post_id, pageviews, visitors) VALUES {$placeholders}", $values));
    }

    protected function seed_referrers(array $referrers, string $date, int $pageviews): void 
    {
        global $wpdb;
        $values = array();
        $pageviews_per_referrer = $pageviews / count($referrers) * 2;
        foreach ($referrers as $referrer_id) {
            $referrer_pageviews = (int) ($pageviews_per_referrer * rand(5, 15) * 0.1);
            $referrer_visitors = (int) (rand(1, 3) * $referrer_pageviews * 0.1);
            array_push($values, $date, $referrer_id, $referrer_pageviews, $referrer_visitors);
        }
        $placeholders = rtrim(str_repeat('(%s,%d,%d,%d),', count($referrers)), ',');
        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}koko_analytics_referrer_stats(date, id, pageviews, visitors) VALUES {$placeholders}", $values));
    }
}
