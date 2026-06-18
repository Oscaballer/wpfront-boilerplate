# -----------------------------------------------
# ETAPA 1: CONSTRUCCIÓN (BUILDER)
# -----------------------------------------------
FROM node:20-alpine AS builder
WORKDIR /app

# 1. Copiar solo package.json y lock para aprovechar caché de Docker
COPY package*.json ./
RUN npm ci

# 2. Copiar el resto del código fuente
COPY . .

# 3. Ejecutar TU script exacto de build standalone
RUN npm run build:standalone

# -----------------------------------------------
# ETAPA 2: EJECUCIÓN (RUNNER)
# -----------------------------------------------
FROM node:20-alpine AS runner
WORKDIR /app

ENV NODE_ENV=production
ENV HOSTNAME=0.0.0.0

# Crear usuario no-root por seguridad (recomendado en prod)
RUN addgroup --system --gid 1001 nodejs && \
    adduser --system --uid 1001 nextjs

# Copiar SOLO el contenido de la carpeta standalone generada
# Esto incluye: server.js, package.json, .next/static y public/
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./

USER nextjs

EXPOSE 3000

# Healthcheck opcional (verifica que Next.js esté listo)
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD wget --no-verbose --tries=1 --spider http://127.0.0.1:3000/favicon.ico || exit 1

# Equivalente a tu script "start": node .next/standalone/server.js
# (Al copiar el contenido de standalone a la raíz, server.js queda en /app)
CMD ["node", "server.js"]