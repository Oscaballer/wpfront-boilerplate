<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MiniCommerce_GCP_Storage {

    const OPTION_BUCKET = 'mc_gcp_bucket';
    const OPTION_JSON   = 'mc_gcp_json';
    const OPTION_HOST   = 'mc_gcp_public_host';

    public function is_configured() {
        return (bool) $this->get_bucket() && (bool) get_option( self::OPTION_JSON );
    }

    public function get_bucket() {
        return trim( (string) get_option( self::OPTION_BUCKET, '' ) );
    }

    public function get_public_host() {
        $host = trim( (string) get_option( self::OPTION_HOST, MC_DEFAULT_GCP_HOST ) );
        return rtrim( $host, '/' );
    }

    public function get_object_name( $filename ) {
        return MC_GCP_PREFIX . ltrim( sanitize_file_name( $filename ), '/' );
    }

    public function get_public_url( $filename ) {
        if ( empty( $filename ) ) {
            return '';
        }
        $parts   = explode( '/', MC_GCP_PREFIX . ltrim( (string) $filename, '/' ) );
        $encoded = implode( '/', array_map( 'rawurlencode', $parts ) );
        return $this->get_public_host() . '/' . $encoded;
    }

    /**
     * @return string|false
     */
    public function get_access_token() {
        $transient_key = 'mc_gcp_access_token';
        $token = get_transient( $transient_key );
        if ( $token ) {
            return $token;
        }

        $json_string = get_option( self::OPTION_JSON );
        if ( ! $json_string ) {
            return false;
        }

        $sa = json_decode( $json_string, true );
        if ( ! $sa || empty( $sa['client_email'] ) || empty( $sa['private_key'] ) ) {
            return false;
        }

        $base64_url = function ( $text ) {
            return str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), base64_encode( $text ) );
        };

        $header = json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) );
        $now    = time();
        $claim  = json_encode(
            array(
                'iss'   => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            )
        );

        $signature_input = $base64_url( $header ) . '.' . $base64_url( $claim );
        $signature       = '';
        openssl_sign( $signature_input, $signature, $sa['private_key'], 'SHA256' );
        $jwt = $signature_input . '.' . $base64_url( $signature );

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            array(
                'body' => array(
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['access_token'] ) ) {
            set_transient( $transient_key, $body['access_token'], 3000 );
            return $body['access_token'];
        }

        return false;
    }

    /**
     * @return true|WP_Error
     */
    public function upload_file( $local_path, $filename, $content_type = null ) {
        if ( ! file_exists( $local_path ) ) {
            return new WP_Error( 'mc_gcp_missing_file', 'Archivo local no encontrado.' );
        }

        $bucket = $this->get_bucket();
        $token  = $this->get_access_token();

        if ( ! $bucket || ! $token ) {
            return new WP_Error( 'mc_gcp_not_configured', 'GCP no configurado o token inválido.' );
        }

        $object_name = $this->get_object_name( $filename );
        $mime        = $content_type ?: ( mime_content_type( $local_path ) ?: 'application/octet-stream' );
        $body        = file_get_contents( $local_path );

        $url = 'https://storage.googleapis.com/upload/storage/v1/b/' . rawurlencode( $bucket ) . '/o?uploadType=media&name=' . rawurlencode( $object_name );

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => $mime,
                ),
                'body'    => $body,
                'timeout' => 120,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            return new WP_Error( 'mc_gcp_upload_failed', 'Error al subir a GCP: HTTP ' . $code );
        }

        return true;
    }

    /**
     * @param array $file Entrada de $_FILES
     * @return true|WP_Error
     */
    public function upload_from_request( $file ) {
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'mc_gcp_invalid_upload', 'Upload inválido.' );
        }

        $filename = sanitize_file_name( $file['name'] );
        return $this->upload_file( $file['tmp_name'], $filename, $file['type'] ?? null );
    }

    public function object_exists( $filename ) {
        $bucket = $this->get_bucket();
        if ( ! $bucket ) {
            return false;
        }

        $object_name = $this->get_object_name( $filename );
        $url         = 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode( $bucket ) . '/o/' . rawurlencode( $object_name );

        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return false;
        }

        return wp_remote_retrieve_response_code( $response ) === 200;
    }

    /**
     * @return true|WP_Error
     */
    public function delete( $filename ) {
        $bucket = $this->get_bucket();
        $token  = $this->get_access_token();

        if ( ! $bucket || ! $token ) {
            return new WP_Error( 'mc_gcp_not_configured', 'GCP no configurado.' );
        }

        $object_name = $this->get_object_name( $filename );
        $url         = 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode( $bucket ) . '/o/' . rawurlencode( $object_name );

        $response = wp_remote_request(
            $url,
            array(
                'method'  => 'DELETE',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 && $code !== 404 ) {
            return new WP_Error( 'mc_gcp_delete_failed', 'Error al eliminar en GCP: HTTP ' . $code );
        }

        return true;
    }
}
