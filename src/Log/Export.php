<?php

namespace TotalContest\Log;

use TotalContest\Contracts\Log\Model as LogModel;
use TotalContest\Writers\PartialCsvWriter;
use TotalContest\Writers\PartialHTMLWriter;
use TotalContest\Writers\PartialJsonWriter;
use TotalContest\Writers\PartialSpreadsheet;
use TotalContestVendors\TotalCore\Export\ColumnTypes\DateColumn;
use TotalContestVendors\TotalCore\Export\ColumnTypes\TextColumn;
use TotalContestVendors\TotalCore\Export\Writer;

/**
 * Log Export Job
 *
 * @package TotalLog\Log
 * @since   1.0.0
 */
class Export {
	const ENQUEUED = 'enqueued';
	const STARTED  = 'started';
	const FINISHED = 'finished';

	const ACTION_NAME = 'totalcontest_export_log';
	const BATCH_SIZE  = 100;

	public static function process( $context ) {
		$export = static::getState( $context['uid'] );

		if ( ! $export ) {
			return new \WP_Error( 'no_such_export', 'No such export job.' );
		}

		if ( $export['status'] === self::ENQUEUED ) {
			$export['status']            = self::STARTED;
			$export['total']             = TotalContest( 'log.repository' )->count( $context['query'] );
			$export['file']              = static::firstWrite( $context );
			$context['query']['page']    = 0;
			$context['query']['perPage'] = self::BATCH_SIZE;
			$context['file']             = $export['file'];
		}

		if ( $export['status'] === self::STARTED ) {
			$context['query']['page'] += 1;
			$entries                  = (array) TotalContest( 'log.repository' )->get( $context['query'] );
			$count                    = count( $entries );
			$export['processed']      += $count;

			if ( $count > 0 ) {
				static::partialWrite( $entries, $context );
			} else {
				$export['status'] = static::FINISHED;
				$export['url']    = TotalContest()->env( 'exports.url' ) . $export['file'];
				static::lastWrite( $context );
			}

			as_enqueue_async_action( self::ACTION_NAME, [ $context ], $export['uid'] );
		}

		static::setState( $export );
	}

	public static function getPartialSpreadsheet( $context ) {
		$export = new PartialSpreadsheet();
		if ( ! empty( $context['query']['conditions']['contest_id'] ) ) {
			$contest        = TotalContest( 'contests.repository' )->getById( $context['query']['conditions']['contest_id'] );
			$formFields     = $contest->getFormFieldsDefinitions();
			$voteFormFields = $contest->getVoteFormFieldsDefinitions();
		} else {
			$formFields     = [];
			$voteFormFields = [];
		}

		foreach ( $context['columns'] as $column ) {
			if ( $column === 'status' ) {
				$export->addColumn( new TextColumn( 'Status' ) );
			} elseif ( $column === 'action' ) {
				$export->addColumn( new TextColumn( 'Action' ) );
			} elseif ( $column === 'date' ) {
				$export->addColumn( new DateColumn( 'Date' ) );
			} elseif ( $column === 'ip' ) {
				$export->addColumn( new TextColumn( 'IP' ) );
			} elseif ( $column === 'browser' ) {
				$export->addColumn( new TextColumn( 'Browser' ) );
			} elseif ( $column === 'user_id' ) {
				$export->addColumn( new TextColumn( 'User ID' ) );
			} elseif ( $column === 'user_login' ) {
				$export->addColumn( new TextColumn( 'User login' ) );
			} elseif ( $column === 'user_name' ) {
				$export->addColumn( new TextColumn( 'User name' ) );
			} elseif ( $column === 'user_email' ) {
				$export->addColumn( new TextColumn( 'User email' ) );
			} elseif ( $column === 'contest' ) {
				$export->addColumn( new TextColumn( 'Contest ID' ) );
			} elseif ( $column === 'submission' ) {
				$export->addColumn( new TextColumn( 'Submission ID' ) );
				$export->addColumn( new TextColumn( 'Submission Title' ) );
				$export->addColumn( new TextColumn( 'Submission URL' ) );
			} elseif ( $column === 'form_field_category' ) {
				if ( ! empty( $formFields['category']['label'] ) ) {
					$export->addColumn( new TextColumn( $formFields['category']['label'] ) );
				} else {
					$export->addColumn( new TextColumn( 'Category' ) );
				}
			} elseif ( $column === 'details' ) {
				$export->addColumn( new TextColumn( 'Details' ) );
			} else {
				$fieldName = str_replace( [ 'form_field_', 'vote_field_' ], '', $column );
				if ( ! empty( $formFields[ $fieldName ]['label'] ) ) {
					$export->addColumn( new TextColumn( $formFields[ $fieldName ]['label'] ) );
				} elseif ( ! empty( $voteFormFields[ $fieldName ]['label'] ) ) {
					$export->addColumn( new TextColumn( $voteFormFields[ $fieldName ]['label'] ) );
				} else {
					$export->addColumn( new TextColumn( $column ) );
				}
			}
		}

		/**
		 * Fires after setup essential columns and before populating data. Useful for define new columns.
		 *
		 * @param  PartialSpreadsheet  $export  PartialSpreadsheet object.
		 * @param  array  $entries  Array of log entries.
		 *
		 * @since 2.0.0
		 */
		do_action( 'totalcontest/actions/admin/log/export/columns', $export );

		return $export;
	}

	public static function getWriter( $format ) {
		
		$writer = new PartialHTMLWriter();
		

		

		/**
		 * Filters the file writer for a specific format when exporting log entries.
		 *
		 * @param  Writer  $writer  Writer object.
		 *
		 * @return Writer
		 * @since 2.0.0
		 */
		$writer = apply_filters( "totalcontest/filters/admin/log/export/writer/{$format}", $writer );

		return $writer;
	}

	public static function firstWrite( $context ) {
		$export = static::getPartialSpreadsheet( $context );
		$writer = static::getWriter( $context['format'] );

		TotalContest( 'utils.create.exports' );
		$filename = sanitize_title_with_dashes( 'totalcontest-export-log-' . date( 'Y-m-d H:i:s' ) ) . '.' . $writer->getDefaultExtension();
		$filename = apply_filters( 'totalcontest/filters/admin/log/export/filename', $filename, $context );
		$path     = TotalContest()->env( 'exports.path' ) . $filename;

		$writer->markAsFirstLine();
		$export->save( $writer, $path );

		return $filename;
	}

	public static function lastWrite( $context ) {
		$export = static::getPartialSpreadsheet( $context );
		$writer = static::getWriter( $context['format'] );

		$writer->markAsLastLine();
		$export->save( $writer, TotalContest()->env( 'exports.path' ) . $context['file'] );
	}

	public static function partialWrite( $entries, $context ) {
		$export = static::getPartialSpreadsheet( $context );
		$writer = static::getWriter( $context['format'] );

		/**
		 * Filters the list of log entries to be exported.
		 *
		 * @param  LogModel[]  $entries  Array of log entries models.
		 *
		 * @return array
		 * @since 2.0.0
		 */
		$entries = apply_filters( 'totalcontest/filters/admin/log/export/entries', $entries );

		foreach ( $entries as $entry ):
			$row = [];
			foreach ( $context['columns'] as $column ) {
				if ( $column === 'status' ) {
					$row[] = $entry->getStatus();
				} elseif ( $column === 'action' ) {
					$row[] = $entry->getAction();
				} elseif ( $column === 'date' ) {
					$row[] = $entry->getDate();
				} elseif ( $column === 'ip' ) {
					$row[] = $entry->getIp();
				} elseif ( $column === 'browser' ) {
					$row[] = $entry->getUseragent();
				} elseif ( $column === 'user_id' ) {
					$row[] = $entry->getUserId() ?: 'N/A';
				} elseif ( $column === 'user_login' ) {
					$row[] = $entry->getUser()->user_login ?: 'N/A';
				} elseif ( $column === 'user_name' ) {
					$row[] = $entry->getUser()->display_name ?: 'N/A';
				} elseif ( $column === 'user_email' ) {
					$row[] = $entry->getUser()->user_email ?: 'N/A';
				} elseif ( $column === 'contest' ) {
					$row[] = $entry->getContestId();
				} elseif ( $column === 'submission' ) {
					$row[] = $entry->getSubmissionId();
					$row[] = get_the_title( $entry->getSubmissionId() );
					$row[] = get_permalink( $entry->getSubmissionId() );
				} elseif ( $column === 'form_field_category' ) {
					$row[] = $entry->getSubmission()->getCategoryName();
				} elseif ( $column === 'details' ) {
					$row[] = $entry->getDetails();
				} else {
					$fieldName = str_replace( [ 'form_field_', 'vote_field_' ], '', $column );
					$row[]     = $entry->getDetail( "fields.$fieldName", $entry->getDetail( $fieldName ) );
				}
			}
			/**
			 * Filters a row of exported log entries.
			 *
			 * @param  array  $row  Array of values.
			 * @param  LogModel  $entry  Log entry model.
			 *
			 * @return array
			 * @since 2.0.0
			 */
			$row = apply_filters(
				'totalcontest/filters/admin/log/export/row',
				$row,
				$entry,
				$context
			);

			$export->addRow( $row );
		endforeach;

		$export->save( $writer, TotalContest()->env( 'exports.path' ) . $context['file'] );
	}

	public static function enqueue( array $query, $format = 'csv', $columns = null ) {
		$context = [
			'query'   => $query,
			'format'  => $format,
			'columns' => $columns,
			'uid'     => wp_generate_uuid4(),
		];

		as_enqueue_async_action( self::ACTION_NAME, [ $context ], $context['uid'] );

		$export = [
			'uid'       => $context['uid'],
			'status'    => 'enqueued',
			'format'    => $format,
			'columns'   => $columns,
			'processed' => 0,
			'total'     => 0,
			'file'      => '',
			'url'       => '',
		];

		static::setState( $export );

		return $export;
	}

	public static function getState( $exportUid ) {
		return get_transient( "totalcontest_export:{$exportUid}" );
	}

	public static function setState( $export ) {
		set_transient( "totalcontest_export:{$export['uid']}", $export, WEEK_IN_SECONDS );
	}
}
