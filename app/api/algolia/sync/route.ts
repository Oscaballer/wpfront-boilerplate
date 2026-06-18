import { NextResponse } from 'next/server';
import { algoliasearch } from 'algoliasearch';
import { graphqlClient } from '@/lib/graphql/client';

export const dynamic = 'force-dynamic';
export const maxDuration = 60; // Allow more time for large syncs

const SYNC_QUERY = `
  query GetAllContentForAlgolia($first: Int!, $after: String) {
    posts(first: $first, after: $after, where: { status: PUBLISH }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        title
        slug
        excerpt
        content
        date
      }
    }
    pages(first: $first, after: $after, where: { status: PUBLISH }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        title
        slug
        content
        date
      }
    }
  }
`;

function stripHtml(html: string | null | undefined): string {
  if (!html) return '';
  return html.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
}

export async function POST(request: Request) {
  try {
    // 1. Validate Secret
    const authHeader = request.headers.get('authorization');
    const secret = process.env.REVALIDATE_SECRET;
    
    // Simple Bearer token check
    if (!secret || authHeader !== `Bearer ${secret}`) {
      // For manual testing from browser, we might want to also allow a query param
      const { searchParams } = new URL(request.url);
      if (searchParams.get('secret') !== secret) {
        return NextResponse.json({ message: 'Unauthorized' }, { status: 401 });
      }
    }

    // 2. Initialize Algolia Client
    const appId = process.env.NEXT_PUBLIC_ALGOLIA_APP_ID;
    const adminKey = process.env.ALGOLIA_ADMIN_API_KEY;
    const indexName = process.env.NEXT_PUBLIC_ALGOLIA_INDEX_NAME || 'wp_searchable_posts';

    if (!appId || !adminKey) {
      return NextResponse.json({ message: 'Missing Algolia credentials in .env' }, { status: 500 });
    }

    const client = algoliasearch(appId, adminKey);
    let allObjects: any[] = [];
    let hasNextPagePosts = true;
    let hasNextPagePages = true;
    let postCursor: string | null = null;
    let pageCursor: string | null = null;

    // 3. Fetch Data in chunks (simplified, usually we'd loop. Here we do 1 large fetch to keep it simple, or loop if needed)
    // For safety, let's fetch up to 500 items in one go, which is usually enough for a university faculty site.
    // We will do a single fetch for both to avoid complex pagination logic in this simple script.
    
    const data = await graphqlClient.request<any>(SYNC_QUERY, { first: 1000, after: null });

    // 4. Map Posts
    if (data.posts && data.posts.nodes) {
      const postObjects = data.posts.nodes.map((post: any) => ({
        objectID: `post_${post.id}`,
        post_title: post.title,
        permalink: `/noticias/${post.slug}`, // Asumiendo que los posts van a /noticias/slug
        content: stripHtml(post.excerpt || post.content),
        type: 'post',
        date: post.date,
      }));
      allObjects = [...allObjects, ...postObjects];
    }

    // 5. Map Pages
    if (data.pages && data.pages.nodes) {
      const pageObjects = data.pages.nodes.map((page: any) => ({
        objectID: `page_${page.id}`,
        post_title: page.title,
        permalink: `/${page.slug}`, // Asumiendo que las páginas van a la raíz o similar
        content: stripHtml(page.content),
        type: 'page',
        date: page.date,
      }));
      allObjects = [...allObjects, ...pageObjects];
    }

    // 6. Push to Algolia
    if (allObjects.length > 0) {
      await client.saveObjects({ indexName, objects: allObjects });
    }

    return NextResponse.json({ 
      success: true, 
      message: `Successfully synced ${allObjects.length} items to Algolia index ${indexName}.`,
      count: allObjects.length 
    });

  } catch (error: any) {
    console.error('Algolia sync error:', error);
    return NextResponse.json({ message: 'Internal Server Error', error: error.message }, { status: 500 });
  }
}

export async function GET(request: Request) {
  return POST(request);
}
