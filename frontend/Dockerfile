# Tahap 1: build aset Vue dengan Node (versi dipin, bukan latest).
FROM node:22.20.0-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Tahap 2: sajikan hasil build lewat Nginx + proxy /api ke service backend.
# TIDAK ADA password/key/token yang ditulis di berkas ini.
FROM nginx:1.27.4-alpine
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
