import { SourceLocation, SourceLocationProps } from './source-location';

export const FileName = ( props: Omit<SourceLocationProps, 'isFileName'> ) => (
	<SourceLocation { ...props } isFileName />
);
