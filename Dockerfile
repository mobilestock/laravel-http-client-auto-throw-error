FROM node:18-alpine

WORKDIR /app

CMD ["node", "comandos/autoPublish/index.js"]
