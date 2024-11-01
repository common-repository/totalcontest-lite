<?php

namespace TotalContest\Limitations;


use TotalContestVendors\TotalCore\Limitations\Limitation;

/**
 * Class Membership
 *
 * @package TotalContest\Limitations
 */
class Membership extends Limitation {
	/**
	 * @return bool|\WP_Error
	 */
	public function check() {
		$roles = empty( $this->args['roles'] ) ? [] : (array) $this->args['roles'];

		if ( ! empty( $roles ) ):

			if ( is_user_logged_in() ):
				if ( empty( array_intersect( array_values( $GLOBALS['current_user']->roles ), $roles ) ) ):
					$defaultMessage = $this->args['context'] === 'vote' ?
						esc_html__( 'To vote, you must be a part of these roles: %s.', 'totalcontest' ) :
						esc_html__( 'To participate, you must be a part of these roles: %s.', 'totalcontest' );

					$defaultMessage = str_replace( '%s', '{{roles}}', $defaultMessage );

					return new \WP_Error(
						'membership_type',
						str_replace( '{{roles}}',
						             implode( ', ', $roles ),
						             empty( $this->args['message'] ) ? $defaultMessage : $this->args['message'] )
					);
				endif;
			else:
				$altLinks = apply_filters( 'totalcontest/filters/contest/membership/redirect', null, $this->args );
				if ( $altLinks !== null ) {
					return $altLinks;
				}

				$defaultMessage = $this->args['context'] === 'vote' ?
					wp_kses( __( 'To vote, please <a href="%s">sign in</a> or <a href="%s">register</a>.',
					             'totalcontest' ), [ 'a' => [ 'href' => [], 'target' => [] ], 'strong' => [] ] ) :
					wp_kses( __( 'To participate, please <a href="%s">sign in</a> or <a href="%s">register</a>.',
					             'totalcontest' ), [ 'a' => [ 'href' => [], 'target' => [] ], 'strong' => [] ] );

				$defaultMessage = str_replace( [ '%s', '%s' ],
				                               [
					                               wp_login_url( home_url( add_query_arg( null, null ) ) ),
					                               wp_registration_url(),
				                               ],
				                               $defaultMessage );

				preg_match( '/(?P<signIn><a.*?\/a>).*(?P<register><a.*?\/a>)/si', $defaultMessage, $matches );

				return new \WP_Error(
					'logged_in',
					str_replace(
						[ '{{signInLink}}', '{{registerLink}}' ],
						[
							empty( $matches['signIn'] ) ? '' : $matches['signIn'],
							empty( $matches['register'] ) ? '' : $matches['register'],
						],
						empty( $this->args['messageVisitor'] ) ? $defaultMessage : $this->args['messageVisitor']
					)
				);
			endif;

		endif;

		return true;
	}
}
