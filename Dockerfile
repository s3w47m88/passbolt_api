FROM nginx:alpine

# Create a simple HTML file
RUN echo '<!DOCTYPE html><html><body><h1>Railway is Working!</h1><p>If you see this, deployment works.</p></body></html>' > /usr/share/nginx/html/index.html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]