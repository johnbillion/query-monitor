import clsx from 'clsx';
import { useState, useRef, useEffect } from 'preact/hooks';

import { __ } from '@wordpress/i18n';

import { Icon } from './components/icon';
import { handleTablistKeydown, numberFormat } from './utils';

/** Reserved request switcher id for the top-level requests overview. */
export const REQUESTS_OVERVIEW_ID = 'qm-requests-overview';

/**
 * Split a path label into its leading portion (right-truncated with an
 * ellipsis) and its final segment (kept visible in full). The final segment is
 * the last slash, the characters after it, and any trailing slash. For
 * `/wp-json/wp/v2/posts` this returns `/wp-json/wp/v2` and `/posts`.
 */
const splitRequestLabel = ( label: string ): { lead: string; segment: string } => {
	// A trailing slash belongs to the final segment, so ignore it when locating
	// the slash that begins that segment.
	const trimmed = label.length > 1 && label.endsWith( '/' ) ? label.slice( 0, -1 ) : label;
	const lastSlash = trimmed.lastIndexOf( '/' );

	if ( lastSlash === -1 ) {
		return { lead: '', segment: label };
	}

	return {
		lead: label.slice( 0, lastSlash ),
		segment: label.slice( lastSlash ),
	};
};

export type iQMRequestStatus = 'idle' | 'loading' | 'loaded' | 'error';

export type iQMRequest = {
	id: string;
	label: string;
	status: iQMRequestStatus;
	method?: string;
	path?: string;
	statusCode?: number | null;
};

interface Props {
	items: iQMRequest[];
	activeRequestId: string;
	onRequestChange: ( id: string ) => void;
	paused?: boolean;
	onPauseToggle: () => void;
	onClear: () => void;
}

export const RequestNav = ( { items, activeRequestId, onRequestChange, paused = false, onPauseToggle, onClear }: Props ) => {
	const [ searchOpen, setSearchOpen ] = useState( false );
	const [ query, setQuery ] = useState( '' );
	const searchInputRef = useRef<HTMLInputElement>( null );

	useEffect( () => {
		if ( searchOpen ) {
			searchInputRef.current?.focus();
		}
	}, [ searchOpen ] );

	if ( items.length < 1 ) {
		return null;
	}

	const requestCount = items.length;

	const trimmedQuery = query.trim().toLowerCase();
	const visibleItems = trimmedQuery
		? items.filter( ( item ) => (
			( item.path ?? '' ).toLowerCase().includes( trimmedQuery ) ||
			item.label.toLowerCase().includes( trimmedQuery )
		) )
		: items;

	const toggleSearch = () => {
		if ( searchOpen ) {
			setQuery( '' );
		}
		setSearchOpen( ! searchOpen );
	};

	return (
		<nav aria-labelledby="qm-request-menu-caption" id="qm-request-menu">
			<h2 className="qm-screen-reader-text" id="qm-request-menu-caption">
				{ __( 'Requests', 'query-monitor' ) }
			</h2>
			<ul role="tablist" onKeyDown={ ( e ) => handleTablistKeydown( e, [
				{ key: 'ArrowRight', id: 'qm-panel-menu' },
			] ) }>
				<li
					className={ clsx( {
						'qm-current-menu': activeRequestId === REQUESTS_OVERVIEW_ID,
					} ) }
					role="presentation"
				>
					<button
						aria-selected={ activeRequestId === REQUESTS_OVERVIEW_ID }
						role="tab"
						onClick={ () => onRequestChange( REQUESTS_OVERVIEW_ID ) }
					>
						<span className="qm-request-label">{ __( 'Overview', 'query-monitor' ) }</span>
						<span aria-hidden="true" className="qm-menu-badge">{ numberFormat( requestCount ) }</span>
					</button>
				</li>
				{ visibleItems.map( ( item ) => {
					const { lead, segment } = splitRequestLabel( item.label );

					return (
					<li
						key={ item.id }
						className={ clsx( {
							'qm-current-menu': item.id === activeRequestId,
						} ) }
						role="presentation"
					>
						<button
							aria-selected={ item.id === activeRequestId }
							role="tab"
							onClick={ () => onRequestChange( item.id ) }
						>
							<span className="qm-request-label">
								{ lead && (
									<span className="qm-request-label-lead">{ lead }</span>
								) }
								<span className="qm-request-label-segment">{ segment }</span>
							</span>
							{ typeof item.statusCode === 'number' && item.statusCode !== 200 && (
								<span
									className={ clsx( 'qm-menu-badge', {
										'qm-menu-badge-warning': item.statusCode < 200 || item.statusCode > 299,
									} ) }
								>
									{ item.statusCode }
								</span>
							) }
							{ item.status === 'error' && (
								<span className="qm-menu-badge qm-menu-badge-warning" aria-hidden="true">!</span>
							) }
						</button>
					</li>
					);
				} ) }
			</ul>
			{ searchOpen && (
				<div className="qm-request-search" id="qm-request-search">
					<input
						ref={ searchInputRef }
						type="search"
						className="qm-request-search-input"
						placeholder={ __( 'Filter requests', 'query-monitor' ) }
						aria-label={ __( 'Filter requests', 'query-monitor' ) }
						value={ query }
						onInput={ ( e ) => setQuery( e.currentTarget.value ) }
					/>
				</div>
			) }
			<div className="qm-request-controls">
				<button
					type="button"
					aria-pressed={ paused }
					title={ paused ? __( 'Resume', 'query-monitor' ) : __( 'Pause', 'query-monitor' ) }
					onClick={ onPauseToggle }
				>
					<Icon name={ paused ? 'record' : 'record-stop' } />
					<span className="qm-screen-reader-text">
						{ paused ? __( 'Resume', 'query-monitor' ) : __( 'Pause', 'query-monitor' ) }
					</span>
				</button>
				<button
					type="button"
					title={ __( 'Clear', 'query-monitor' ) }
					onClick={ onClear }
				>
					<Icon name="ban" />
					<span className="qm-screen-reader-text">
						{ __( 'Clear', 'query-monitor' ) }
					</span>
				</button>
				<button
					type="button"
					aria-expanded={ searchOpen }
					aria-controls="qm-request-search"
					title={ __( 'Filter requests', 'query-monitor' ) }
					onClick={ toggleSearch }
				>
					<Icon name="search" />
					<span className="qm-screen-reader-text">
						{ __( 'Filter requests', 'query-monitor' ) }
					</span>
				</button>
			</div>
		</nav>
	);
};
