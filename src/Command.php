<?php

namespace KokoAnalytics\Seeder;

use WP_CLI;

class Command
{
    // Ensures site has at least 100 posts
    private function create_posts(): array
    {
        global $wpdb;

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish'");
        $n =  max(0, (100 - $count));
        echo "Creating $n posts\n";

        for ($i = 0; $i < $n; $i++) {
            wp_insert_post([
                'post_status' => 'publish',
                'post_title' => "Post #{$i}",
                'post_content' => "Hello world. This is the content of post #{$i}.",
            ]);
        }

        return array_map(function ($row) {
            return $row->ID;
        }, $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish'"));
    }

    // creates 25 referrer url's
    public function create_referrer_urls(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'koko_analytics_referrer_urls';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $n = 25 - $count;
        echo "Creating $n referrer URL's\n";

        for ($i = 0; $i < $n; $i++) {
            $s = rand(0, 10) < 5 ? 's' : '';
            $url = "http$s://referrer-$i.com/";
            $wpdb->insert(
                $table,
                array(
                    'url' => $url,
                )
            );
        }

        return array_map(function ($row) {
            return $row->id;
        }, $wpdb->get_results("SELECT id FROM $table"));
    }


    // creates 25 unique paths
    public function create_paths(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'koko_analytics_paths';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $n = 25 - $count;
        echo "Creating $n unique paths\n";

        for ($i = 0; $i < $n; $i++) {
            $random_string = bin2hex(random_bytes(4));
            $wpdb->insert(
                $table,
                array(
                    'path' => "/path-{$random_string}/",
                )
            );
        }

        return array_map(function ($row) {
            return $row->id;
        }, $wpdb->get_results("SELECT id FROM $table"));
    }


    public function __invoke($args)
    {
        global $wpdb;

        // empty current data
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_site_stats");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_post_stats");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_referrer_stats");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_referrer_urls");
        $wpdb->query("TRUNCATE {$wpdb->prefix}koko_analytics_paths");

        $posts = $this->create_posts();
        $referrers = $this->create_referrer_urls();
        $paths = $this->create_paths();
        $post_count = count($posts);
        $referrer_count = count($referrers);

        for ($dt = new \DateTimeImmutable('now'); $dt > new \DateTimeImmutable('-3 years'); $dt = $dt->modify('-1 day')) {
            $date = $dt->format('Y-m-d');
            echo "Seeding stats for date {$date}\n";

            $pageviews = rand(1000, 10000);
            $visitors = rand(1, 3) * $pageviews * 0.1;

            // simulate a huge peak in traffic every 180 days
            if (rand(1, 180) === 1) {
                $pageviews = $pageviews * 10;
                $visitors  = $visitors * 10;
            }

            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}koko_analytics_site_stats(date, pageviews, visitors) VALUES(%s, %d, %d)", array($date, $pageviews, $visitors)));
            $values = array();
            $pageviews_per_post = $pageviews / $post_count * 2;
            $value_count = 0;
            for ($i = 0; $i < rand(5, 15); $i++) {
                $path_id = $paths[array_rand($paths)];
                $post_id = $posts[array_rand($posts)];
                $value_count++;
                $post_pageviews = (int) ($pageviews_per_post * rand(5, 15) * 0.1);
                $post_visitors = (int) (rand(1, 3) * $post_pageviews * 0.1);
                array_push($values, $date, $path_id, $post_id, $post_pageviews, $post_visitors);
            }
            $placeholders = rtrim(str_repeat('(%s,%d,%d,%d,%d),', $value_count), ',');
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}koko_analytics_post_stats(date, path_id, post_id, pageviews, visitors) VALUES {$placeholders}", $values));

            $value_count = 0;
            $values = array();
            $pageviews_per_referrer = $pageviews / $referrer_count * 2;
            foreach ($referrers as $referrer_id) {
                $value_count++;
                $referrer_pageviews = (int) ($pageviews_per_referrer * rand(5, 15) * 0.1);
                $referrer_visitors = (int) (rand(1, 3) * $referrer_pageviews * 0.1);
                array_push($values, $date, $referrer_id, $referrer_pageviews, $referrer_visitors);
            }
            $placeholders = rtrim(str_repeat('(%s,%d,%d,%d),', $value_count), ',');
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}koko_analytics_referrer_stats(date, id, pageviews, visitors) VALUES {$placeholders}", $values));
        }
    }
}
