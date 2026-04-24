import { liteClient as algoliasearch } from 'algoliasearch/lite';

const appId = process.env.NEXT_PUBLIC_ALGOLIA_APP_ID;
const apiKey = process.env.NEXT_PUBLIC_ALGOLIA_SEARCH_API_KEY;

// Only initialize if variables are present to avoid build errors if not configured yet
export const searchClient = appId && apiKey ? algoliasearch(appId, apiKey) : null;
export const algoliaIndexName = process.env.NEXT_PUBLIC_ALGOLIA_INDEX_NAME || 'wp_posts';
