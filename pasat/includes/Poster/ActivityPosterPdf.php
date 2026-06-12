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
			__( 'Capacity: %d', 'pasat' ),
			(int) $activity['capacity']
		) : __( 'Signup required', 'pasat' );
		$qr_url       = Helpers::activity_qr_url( (int) ( $activity['id'] ?? 0 ) );
		$signup_url   = Helpers::public_signup_url( (int) ( $activity['id'] ?? 0 ) );
		$short_url    = $qr_url ?: $signup_url;
		$matrix       = QrCode::matrix( $qr_url );

		$content  = $this->rect( 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, array( 0.955, 0.972, 0.984 ) );
		$content .= $this->rect( 0, 724, self::PAGE_WIDTH, 118, array( 0.054, 0.137, 0.224 ) );
		$content .= $this->rect( 0, 718, self::PAGE_WIDTH, 6, array( 0.086, 0.361, 0.455 ) );
		$content .= $this->rect( 46, 514, 503, 180, array( 1, 1, 1 ) );
		$content .= $this->stroke_rect( 46, 514, 503, 180, array( 0.78, 0.84, 0.88 ), 1 );
		$content .= $this->rect( 139, 196, 318, 286, array( 1, 1, 1 ) );
		$content .= $this->stroke_rect( 139, 196, 318, 286, array( 0.78, 0.84, 0.88 ), 1 );
		$content .= $this->rect( 46, 78, 503, 84, array( 1, 1, 1 ) );
		$content .= $this->stroke_rect( 46, 78, 503, 84, array( 0.78, 0.84, 0.88 ), 1 );

		if ( $logo ) {
			$content .= $this->image( 48, 765, 176, 46, (int) $logo['width'], (int) $logo['height'] );
		} else {
			$content .= $this->wrapped_text( 48, 790, $organization, 18, 21, 2, 'F2', array( 1, 1, 1 ), 250 );
		}

		$content .= $this->text( 420, 792, __( 'PASAT Signup', 'pasat' ), 13, 'F2', array( 1, 1, 1 ) );
		$content .= $this->text( 420, 772, __( 'Scan at the venue', 'pasat' ), 11, 'F1', array( 0.86, 0.91, 0.96 ) );
		$content .= $this->wrapped_text( 64, 666, $title, 25, 28, 3, 'F2', array( 0.054, 0.137, 0.224 ), 466 );
		$content .= $this->text( 64, 584, __( 'When', 'pasat' ), 10, 'F2', array( 0.35, 0.39, 0.45 ) );
		$content .= $this->wrapped_text( 64, 562, $date ?: __( 'Time to be announced', 'pasat' ), 16, 19, 2, 'F2', array( 0.054, 0.137, 0.224 ), 220 );
		$content .= $this->text( 314, 584, __( 'Where', 'pasat' ), 10, 'F2', array( 0.35, 0.39, 0.45 ) );
		$content .= $this->wrapped_text( 314, 562, $venue ?: __( 'Venue to be announced', 'pasat' ), 16, 19, 2, 'F2', array( 0.054, 0.137, 0.224 ), 210 );
		if ( '' !== $address ) {
			$content .= $this->wrapped_text( 314, 524, $address, 9, 12, 2, 'F1', array( 0.35, 0.39, 0.45 ), 210 );
		}
		$content .= $this->text( 64, 524, $capacity, 11, 'F2', array( 0.35, 0.39, 0.45 ) );

		$content .= $this->centered_text( self::PAGE_WIDTH / 2, 492, __( 'Scan to sign up', 'pasat' ), 26, 'F2', array( 0.054, 0.137, 0.224 ) );
		if ( $matrix ) {
			$content .= $this->qr( $matrix, 171, 220, 254 );
		} else {
			$content .= $this->wrapped_text( 174, 350, __( 'QR code unavailable for this URL. Use the signup link below.', 'pasat' ), 14, 18, 4, 'F2', array( 0.054, 0.137, 0.224 ), 246 );
		}
		$content .= $this->centered_text( self::PAGE_WIDTH / 2, 176, __( 'Open the camera on your phone and point it at the code.', 'pasat' ), 10, 'F1', array( 0.35, 0.39, 0.45 ) );

		$content .= $this->text( 64, 134, __( 'Direct signup link', 'pasat' ), 10, 'F2', array( 0.35, 0.39, 0.45 ) );
		$content .= $this->wrapped_text( 64, 112, $short_url, 14, 17, 2, 'F2', array( 0.054, 0.137, 0.224 ), 460 );
		$content .= $this->text( 64, 92, __( 'This short link opens the signup form for this activity.', 'pasat' ), 9, 'F1', array( 0.35, 0.39, 0.45 ) );
		$content .= $this->text( 46, 44, sprintf(
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

	private function rect( float $x, float $y, float $width, float $height, array $color ): string {
		return sprintf(
			"%.3F %.3F %.3F rg %.3F %.3F %.3F %.3F re f\n",
			$color[0],
			$color[1],
			$color[2],
			$x,
			$y,
			$width,
			$height
		);
	}

	private function stroke_rect( float $x, float $y, float $width, float $height, array $color, float $line_width = 1 ): string {
		return sprintf(
			"%.3F %.3F %.3F RG %.3F w %.3F %.3F %.3F %.3F re S\n",
			$color[0],
			$color[1],
			$color[2],
			$line_width,
			$x,
			$y,
			$width,
			$height
		);
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

	private function centered_text( float $center_x, float $y, string $text, float $size, string $font, array $color ): string {
		$plain = $this->plain_text( $text );
		$width = strlen( $plain ) * $size * 0.52;
		return $this->text( $center_x - $width / 2, $y, $plain, $size, $font, $color );
	}

	private function wrapped_text( float $x, float $y, string $text, float $size, float $line_height, int $max_lines, string $font, array $color, float $max_width = 520 ): string {
		$max_chars = max( 8, (int) floor( $max_width / max( 1, $size * 0.52 ) ) );
		$lines     = explode( "\n", wordwrap( $this->plain_text( $text ), $max_chars, "\n", true ) );
		$truncated = count( $lines ) > $max_lines;
		$lines     = array_slice( $lines, 0, $max_lines );
		$out       = '';

		if ( $truncated && $lines ) {
			$last = rtrim( (string) end( $lines ) );
			$lines[ count( $lines ) - 1 ] = rtrim( substr( $last, 0, max( 0, $max_chars - 3 ) ) ) . '...';
		}

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
