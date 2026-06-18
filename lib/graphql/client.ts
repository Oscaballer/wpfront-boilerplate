/**
 * lib/graphql/client.ts
 *
 * Cliente GraphQL usando fetch nativo — integrado con el data cache
 * de Next.js para soportar revalidación bajo demanda (ISR) por tags.
 */
const endpoint = `${process.env.WORDPRESS_URL ?? process.env.NEXT_PUBLIC_WORDPRESS_URL ?? "http://localhost:8080"
    }/graphql`;

console.log('[GraphQL Client] Endpoint:', endpoint);

export interface GraphQLRequestOptions {
    /** Next.js cache tags para on-demand revalidation (revalidateTag). */
    tags?: string[];
}



export const graphqlClient = {
    async request<T>(
        query: string,
        variables?: Record<string, unknown>,
        options?: GraphQLRequestOptions
    ): Promise<T> {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query, variables }),

            // 🔴 ESTE ES EL CAMBIO CLAVE
            next: {
                revalidate: 86400,
                tags: options?.tags ?? [],
            },
        });

        if (!res.ok) {
            const errorBody = await res.text();
            throw new Error(`GraphQL request failed: ${res.status} - ${errorBody}`);
        }

        const json = await res.json();

        if (json.errors) {
            throw new Error(json.errors[0]?.message || 'GraphQL error');
        }

        return json.data as T;
    },
};