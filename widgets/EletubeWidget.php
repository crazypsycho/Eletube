<?php

namespace Eletube;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
final class EletubeWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'eletube';
    }

    /**
     * Get widget title.
     * @return string Widget title.
     */
    public function get_title() {
        return 'Eletube';
    }

    /**
     * Get widget icon.
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-video';
    }

    /**
     * Get widget categories.
     *
     * Retrieve the list of categories the oEmbed widget belongs to.
     *
     * @return array Widget categories.
     * @since 1.0.0
     * @access public
     */
    public function get_categories() {
        return [ 'general' ];
    }

    /**
     * Get widget keywords.
     *
     * @return array Widget keywords.
     */
    public function get_keywords() {
        return [ 'youtube', 'video' ];
    }

    /**
     * Register widget controls.
     *
     * Add input fields to allow the user to customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__( 'Content' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'apikey',
            [
                'label' => 'Api-Key',
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
            ]
        );
        $this->add_control(
            'channelId',
            [
                'label' => 'Channel-Id',
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
            ]
        );

        $this->add_control(
            'startId',
            [
                'label' => 'Start-Id',
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
            ]
        );

        $this->end_controls_section();

    }

    private function getYoutubeData( $settings ) {
        $cacheFilename = $settings['apikey'] . '-' . $settings['channelId'];
        $cacheFilepath = Eletube_CACHE_DIR . '/' . $cacheFilename . '.json';
        if ( file_exists( $cacheFilepath ) && filemtime( $cacheFilepath ) > time() - ( 60 * 60 * 24 ) ) {
            return json_decode( file_get_contents( $cacheFilepath ) );
        }

        $baseUrl = 'https://youtube.googleapis.com/youtube/v3/playlistItems?part=snippet&part=contentDetails&maxResults=50&playlistId=' . $settings['channelId'] . '&prettyPrint=true&key=' . $settings['apikey'];
        $items = [];
        $pageToken = '';
        do {
            $url = $baseUrl . '&pageToken=' . $pageToken;
            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $url );
            curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)' );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 5 );
            curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );
            $data = json_decode( curl_exec( $curl ), true );
            if ( empty( $data['items'] ) ) {
                break;
            }
            $items += $data['items'];
            if ( !empty( $data['nextPageToken'] ) ) {
                $pageToken = $data['nextPageToken'];
            }

        } while ( !empty( $data['nextPageToken'] ) );

        file_put_contents( $cacheFilepath, json_encode( $items ) );

        return $items;
    }

    /**
     * Render oEmbed widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function render() {

        $settings = $this->get_settings_for_display();
        $youtubeData = $this->getYoutubeData( $settings );

        $startVideo = $youtubeData[0];
        if ( $settings['startId'] ) {
            foreach ( $youtubeData as $video ) {
                if ($video->snippet->resourceId->videoId === $settings['startId'] ) {
                    $startVideo = $video;
                    break;
                }
            }
        }

        $this->renderIframe( $startVideo );

        echo '<div class="eletube-item-list">';
        foreach ( $youtubeData as $item ) {
            $this->renderItem( $item );
        }
        echo '</div>';
    }

    private function renderItem( $video ) {
        $template = file_get_contents( Eletube_URL . '/templates/item.html' );

        echo $this->replaceVariables( $template, $video );

    }

    private function renderIframe( $video ) {
        $template = file_get_contents( Eletube_URL . '/templates/video.html' );

        echo $this->replaceVariables( $template, $video );
    }

    private function replaceVariables( $template, $video ) {
        $videoUrl = $this->getVideoUrl( $video->snippet->resourceId->videoId );

        $template = str_replace( '{{videoUrl}}', $videoUrl, $template );
        $highUrl = $video->snippet->thumbnails->high->url;
        $maxresUrl = $highUrl;
        if (!empty($video->snippet->thumbnails->maxres)) {
            $maxresUrl = $video->snippet->thumbnails->maxres->url;
        }
        $template = str_replace( '{{fullVideoImage}}', $this->getLocalImage( $maxresUrl ), $template );
        $template = str_replace( '{{highVideoImage}}', $this->getLocalImage( $highUrl ), $template );
        $template = str_replace( '{{title}}', $video->snippet->title, $template );
        $template = str_replace( '{{description}}', nl2br( $video->snippet->description ), $template );

        return $template;
    }

    private function getLocalImage( $originalImageUrl ) {
        $filename = str_replace( [ 'https://', '/' ], [ '', '-' ], $originalImageUrl );
        $path = Eletube_CACHE_DIR . '/thumbnails/' . $filename;

        if ( !file_exists( $path ) || filemtime( $path ) > time() - ( 60 * 60 * 60 * 24 ) ) {
            file_put_contents( $path, file_get_contents( $originalImageUrl ) );
        }

        return wp_upload_dir()['url'] . '/eletube-cache/thumbnails/' . $filename;
    }

    private function getVideoUrl( $videoId ) {
        return 'https://www.youtube-nocookie.com/embed/' . $videoId . '?controls=1&rel=0&playsinline=0&modestbranding=0&autoplay=1&enablejsapi=1';
    }

}
