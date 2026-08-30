import { cleanup } from '@testing-library/preact';
import { afterEach } from 'vitest';

// Without this each render() accumulates in the same document and the `screen`
// queries match components left over from earlier tests.
afterEach( cleanup );
