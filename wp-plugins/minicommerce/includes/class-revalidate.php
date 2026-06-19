<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MiniCommerce_Revalidate {

    const OPTION_URL    = 'mc_revalidate_url';
    const OPTION_SECRET = 'mc_revalidate_secret';

    public static function init() {
        // Hooks para fases posteriores (CRUD admin).
    }

    /**
     * @param string[] $tags
     */
    public static function trigger( array $tags = array( 'minicommerce' ) ) {
        $url    = trim( (string) get_option( self::OPTION_URL, '' ) );
        $secret = trim( (string) get_option( self::OPTION_SECRET, '' ) );

        if ( ! $url || ! $secret ) {
            return false;
        }

        $results = array();

        foreach ( $tags as $tag ) {
            $response = wp_remote_post(
                $url,
                array(
                    'headers' => array(
                        'Content-Type'         => 'application/json',
                        'x-revalidate-secret'  => $secret,
                    ),
                    'body'    => wp_json_encode( array( 'tag' => $tag ) ),
                    'timeout' => 15,
                )
            );

            $results[ $tag ] = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 400;
        }

        return $results;
    }
}
