const ID_HEADER = 'X-QM-Request-ID';
const DATA_HEADER = 'X-QM-Request-Data';

export type DetectedRequest = {
	id: string;
	dataUrl: string;
	requestUrl: string;
	method: string;
	statusCode: number;
};

export type ObserveOptions = {
	ignoreHeartbeat?: boolean;
	ajaxurl?: string;
};

let installed = false;

/**
 * Reads the `action` value from a request body, regardless of whether it was
 * sent as a URL-encoded string, URLSearchParams, or FormData.
 */
function getRequestAction( body: unknown ): string | null {
	if ( typeof body === 'string' ) {
		return new URLSearchParams( body ).get( 'action' );
	}

	if ( body instanceof URLSearchParams ) {
		return body.get( 'action' );
	}

	if ( body instanceof FormData ) {
		const action = body.get( 'action' );
		return typeof action === 'string' ? action : null;
	}

	return null;
}

/**
 * Installs observers on `XMLHttpRequest` and `fetch` that report any response
 * carrying a Query Monitor request ID header.
 */
export function observeQMRequests(
	onDetect: ( request: DetectedRequest ) => void,
	options: ObserveOptions = {},
): void {
	if ( installed ) {
		return;
	}
	installed = true;

	const { ignoreHeartbeat = true, ajaxurl = '' } = options;

	const matchesAjaxurl = ( url: string ): boolean => {
		if ( ! url || ! ajaxurl ) {
			return false;
		}
		try {
			return ( new URL( url, window.location.href ).pathname === new URL( ajaxurl, window.location.href ).pathname );
		} catch {
			return false;
		}
	};

	const isHeartbeat = ( method: string, url: string, body: unknown ): boolean => {
		if ( ! ignoreHeartbeat || method.toUpperCase() !== 'POST' || ! matchesAjaxurl( url ) ) {
			return false;
		}
		return getRequestAction( body ) === 'heartbeat';
	};

	const report = (
		id: string | null,
		dataUrl: string | null,
		requestUrl: string,
		method: string,
		statusCode: number,
	) => {
		if ( id && dataUrl ) {
			onDetect( { id, dataUrl, requestUrl, method, statusCode } );
		}
	};

	const requestInfo = new WeakMap<XMLHttpRequest, { method: string; url: string }>();

	const originalOpen = XMLHttpRequest.prototype.open;

	XMLHttpRequest.prototype.open = function (
		this: XMLHttpRequest,
		method: string,
		url: string | URL,
		...rest: unknown[]
	) {
		requestInfo.set( this, { method, url: String( url ) } );
		return originalOpen.apply( this, [ method, url, ...rest ] as Parameters<XMLHttpRequest[ 'open' ]> );
	};

	const originalSend = XMLHttpRequest.prototype.send;

	XMLHttpRequest.prototype.send = function (
		this: XMLHttpRequest,
		...args: Parameters<XMLHttpRequest[ 'send' ]>
	) {
		const info = requestInfo.get( this );
		if ( ! isHeartbeat( info?.method ?? 'GET', info?.url ?? '', args[ 0 ] ) ) {
			this.addEventListener( 'load', () => {
				report(
					this.getResponseHeader( ID_HEADER ),
					this.getResponseHeader( DATA_HEADER ),
					info?.url ?? '',
					info?.method ?? 'GET',
					this.status,
				);
			} );
		}
		return originalSend.apply( this, args );
	};

	const originalFetch = window.fetch;

	window.fetch = function ( input: RequestInfo | URL, init?: RequestInit ) {
		return originalFetch( input, init ).then( ( response ) => {
			const requestUrl = typeof input === 'string'
				? input
				: input instanceof Request ? input.url : String( input );
			const method = init?.method ?? ( input instanceof Request ? input.method : 'GET' );

			if ( isHeartbeat( method, requestUrl, init?.body ) ) {
				return response;
			}

			report(
				response.headers.get( ID_HEADER ),
				response.headers.get( DATA_HEADER ),
				requestUrl,
				method,
				response.status,
			);

			return response;
		} );
	};
}
