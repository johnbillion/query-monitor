declare module 'react-dom/client' {
	export function createRoot(container: Element): {
		render(element: import('react').ReactNode): void;
		unmount(): void;
	};
}

interface ImportMeta {
	hot?: {
		accept(): void;
	};
}
