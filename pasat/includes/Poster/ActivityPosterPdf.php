<?php
namespace PASAT\Poster;

use PASAT\Helpers;
use PASAT\Security\QrCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityPosterPdf {
	private const PAGE_WIDTH = 595.28;
	private const PAGE_HEIGHT = 841.89;

	private array $objects = array();

	public static function render( array $activity ): string {
		return ( new self() )->build( $activity );
	}

	private function build( array $activity ): string {
		$this->objects = array();

		$pages_id   = $this->reserve_object();
		$page_id    = $this->reserve_object();
		$font_id    = $this->add_object( '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>' );
		$bold_id    = $this->add_object( '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>' );
		$logo       = $this->logo_image();
		$logo_id    = $logo ? $this->add_stream_object(
			'<< /Type /XObject /Subtype /Image /Width ' . absint( $logo['width'] ) . ' /Height ' . absint( $logo['height'] ) . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode',
			$logo['data']
		) : 0;
		$content_id = $this->add_stream_object( '<<', $this->content( $activity, $logo ) );

		$resources = '<< /Font << /F1 ' . $font_id . ' 0 R /F2 ' . $bold_id . ' 0 R >>';
		if ( $logo_id > 0 ) {
			$resources .= ' /XObject << /Im1 ' . $logo_id . ' 0 R >>';
		}
		$resources .= ' >>';

		$this->set_object(
			$page_id,
			'<< /Type /Page /Parent ' . $pages_id . ' 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources ' . $resources . ' /Contents ' . $content_id . ' 0 R >>'
		);
		$this->set_object( $pages_id, '<< /Type /Pages /Kids [' . $page_id . ' 0 R] /Count 1 >>' );
		$catalog_id = $this->add_object( '<< /Type /Catalog /Pages ' . $pages_id . ' 0 R >>' );

		return $this->finish( $catalog_id );
	}

	private function content( array $activity, ?array $logo ): string {
		$title        = $activity['title'] ?? __( 'Activity Signup', 'pasat' );
		$organization = (string) Helpers::setting( 'organization_name', get_bloginfo( 'name' ) );
		$venue        = trim( (string) ( $activity['venue_name'] ?? '' ) );
		$address      = trim( (string) ( $activity['venue_address'] ?? '' ) );
		$date         = Helpers::local_datetime( $activity['starts_at'] ?? '' );
		$capacity     = isset( $activity['capacity'] ) && null !== $activity['capacity'] ? sprintf(
			/* translators: %d is the activity capacity. */
			__( '%d spots available', 'pasat' ),
			(int) $activity['capacity']
		) : __( 'Signup required', 'pasat' );
		$qr_url       = Helpers::activity_qr_url( (int) ( $activity['id'] ?? 0 ) );
		$signup_url   = Helpers::public_signup_url( (int) ( $activity['id'] ?? 0 ) );
		$matrix       = QrCode::matrix( $qr_url );

		$content  = "1 1 1 rg 0 0 " . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . " re f\n";
		$content .= "0.054 0.137 0.224 rg 0 718 " . self::PAGE_WIDTH . " 124 re f\n";
		$content .= "0.969 0.980 0.992 rg 0 0 " . self::PAGE_WIDTH . " 718 re f\n";
		$content .= "0.078 0.173 0.286 rg 42 514 511 170 re f\n";
		$content .= "1 1 1 rg 48 520 499 158 re f\n";
		$content .= "0.054 0.137 0.224 rg 56 86 483 92 re f\n";

		if ( $logo ) {
			$content .= $this->image( 56, 752, min( 158, (float) $logo['width'] ), min( 58, (float) $logo['height'] ), (int) $logo['width'], (int) $logo['height'] );
		} else {
			$content .= $this->text( 56, 786, $organization, 18, 'F2', array( 1, 1, 1 ) );
		}

		$content .= $this->text( 420, 792, __( 'PASAT Signup', 'pasat' ), 13, 'F2', array( 1, 1, 1 ) );
		$content .= $this->text( 420, 772, __( 'Scan at the venue', 'pasat' ), 11, 'F1', array( 0.86, 0.91, 0.96 ) );
		$content .= $this->wrapped_text( 56, 660, $title, 32, 24, 3, 'F2', array( 0.054, 0.137, 0.224 ) );
		$content .= $this->text( 56, 592, __( 'When', 'pasat' ), 11, 'F2', array( 0.35, 0.39, 0.45 ) );
		$content .= $this->text( 56, 568, $date ?: __( 'Time to be announced', 'pasat' ), 18, 'F2', array( 0.054, 0.137, 0.224 ) );
		$content .= $this->text( 320, 592, __( 'Where', 'pasat' ), 11, 'F2', array( 0.35, 0.39, 0.45 ) );
		$content .= $this->wrapped_text( 320, 568, $venue ?: __( 'Venue to be announced', 'pasat' ), 18, 22, 2, 'F2', array( 0.054, 0.137, 0.224 ) );
		if ( '' !== $address ) {
			$content .= $this->wrapped_text( 320, 520, $address, 10, 13, 2, 'F1', array( 0.35, 0.39, 0.45 ) );
		}
		$content .= $this->text( 56, 530, $capacity, 13, 'F1', array( 0.35, 0.39, 0.45 ) );

		$content .= "1 1 1 rg 156 230 284 284 re f\n";
		$content .= "0.054 0.137 0.224 RG 156 230 284 284 re S\n";
		if ( $matrix ) {
			$content .= $this->qr( $matrix, 178, 252, 240 );
		} else {
			$content .= $this->wrapped_text( 190, 376, __( 'QR code unavailable for this URL. Use the signup link below.', 'pasat' ), 14, 18, 4, 'F2', array( 0.054, 0.137, 0.224 ) );
		}

		$content .= $this->text( 194, 204, __( 'Scan to sign up', 'pasat' ), 28, 'F2', array( 0.054, 0.137, 0.224 ) );
		$content .= $this->text( 56, 145, __( 'Direct signup link', 'pasat' ), 11, 'F2', array( 1, 1, 1 ) );
		$content .= $this->wrapped_text( 56, 124, $signup_url, 10, 13, 5, 'F1', array( 1, 1, 1 ) );
		$content .= $this->text( 56, 52, sprintf(
			/* translators: %s is the organization name. */
			__( 'Powered by %s', 'pasat' ),
			$organization
		), 10, 'F1', array( 0.35, 0.39, 0.45 ) );

		return $content;
	}

	private function qr( array $matrix, float $x, float $y, float $size ): string {
		$count  = count( $matrix );
		$module = $size / $count;
		$out    = "0 0 0 rg\n";

		foreach ( $matrix as $row => $cols ) {
			foreach ( $cols as $col => $dark ) {
				if ( $dark ) {
					$out .= sprintf( "%.3F %.3F %.3F %.3F re f\n", $x + $col * $module, $y + ( $count - $row - 1 ) * $module, $module + 0.02, $module + 0.02 );
				}
			}
		}

		return $out;
	}

	private function image( float $x, float $y, float $max_width, float $max_height, int $width, int $height ): string {
		$ratio = min( $max_width / max( 1, $width ), $max_height / max( 1, $height ) );
		$draw_width  = $width * $ratio;
		$draw_height = $height * $ratio;

		return sprintf( "q %.3F 0 0 %.3F %.3F %.3F cm /Im1 Do Q\n", $draw_width, $draw_height, $x, $y );
	}

	private function text( float $x, float $y, string $text, float $size, string $font, array $color ): string {
		return sprintf(
			"%.3F %.3F %.3F rg BT /%s %.3F Tf %.3F %.3F Td (%s) Tj ET\n",
			$color[0],
			$color[1],
			$color[2],
			$font,
			$size,
			$x,
			$y,
			$this->pdf_text( $text )
		);
	}

	private function wrapped_text( float $x, float $y, string $text, float $size, float $line_height, int $max_lines, string $font, array $color ): string {
		$max_chars = max( 12, (int) floor( 520 / max( 1, $size ) ) );
		$lines     = explode( "\n", wordwrap( $this->plain_text( $text ), $max_chars, "\n", true ) );
		$lines     = array_slice( $lines, 0, $max_lines );
		$out       = '';

		foreach ( $lines as $index => $line ) {
			$out .= $this->text( $x, $y - $index * $line_height, $line, $size, $font, $color );
		}

		return $out;
	}

	private function logo_image(): ?array {
		$attachment_id = absint( Helpers::setting( 'poster_logo_id', 0 ) );
		if ( ! $attachment_id ) {
			return null;
		}

		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! is_readable( $path ) ) {
			return null;
		}

		$converted = $this->convert_logo_to_jpeg( $path );
		if ( $converted ) {
			return $converted;
		}

		$info = getimagesize( $path );
		if ( ! $info || empty( $info['mime'] ) || 'image/jpeg' !== $info['mime'] ) {
			return null;
		}

		$data = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local media attachment for binary PDF embedding.
		if ( false === $data ) {
			return null;
		}

		return array( 'data' => $data, 'width' => (int) $info[0], 'height' => (int) $info[1] );
	}

	private function convert_logo_to_jpeg( string $path ): ?array {
		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return null;
		}

		$editor->set_quality( 90 );
		$editor->resize( 900, 360, false );

		$tmp = trailingslashit( get_temp_dir() ) . 'pasat-poster-logo-' . wp_generate_uuid4() . '.jpg';
		$saved = $editor->save( $tmp, 'image/jpeg' );
		if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_readable( $saved['path'] ) ) {
			return null;
		}

		$data = file_get_contents( $saved['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a temporary converted logo for binary PDF embedding.
		$info = getimagesize( $saved['path'] );
		wp_delete_file( $saved['path'] );

		if ( false === $data || ! $info ) {
			return null;
		}

		return array( 'data' => $data, 'width' => (int) $info[0], 'height' => (int) $info[1] );
	}

	private function plain_text( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
		$text = remove_accents( $text );
		$text = preg_replace( '/[^\x20-\x7E]/', '', $text ) ?? '';
		return trim( preg_replace( '/\s+/', ' ', $text ) ?? '' );
	}

	private function pdf_text( string $text ): string {
		$text = $this->plain_text( $text );
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
	}

	private function reserve_object(): int {
		$this->objects[] = '';
		return count( $this->objects );
	}

	private function set_object( int $id, string $body ): void {
		$this->objects[ $id - 1 ] = $body;
	}

	private function add_object( string $body ): int {
		$this->objects[] = $body;
		return count( $this->objects );
	}

	private function add_stream_object( string $dict, string $data ): int {
		return $this->add_object( $dict . ' /Length ' . strlen( $data ) . " >>\nstream\n" . $data . "\nendstream" );
	}

	private function finish( int $catalog_id ): string {
		$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array( 0 );

		foreach ( $this->objects as $index => $body ) {
			$offsets[] = strlen( $pdf );
			$id       = $index + 1;
			$pdf     .= $id . " 0 obj\n" . $body . "\nendobj\n";
		}

		$xref = strlen( $pdf );
		$pdf .= "xref\n0 " . ( count( $this->objects ) + 1 ) . "\n";
		$pdf .= "0000000000 65535 f \n";
		for ( $i = 1; $i < count( $offsets ); ++$i ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
		}
		$pdf .= "trailer\n<< /Size " . ( count( $this->objects ) + 1 ) . ' /Root ' . $catalog_id . " 0 R >>\n";
		$pdf .= "startxref\n" . $xref . "\n%%EOF\n";

		return $pdf;
	}
}
