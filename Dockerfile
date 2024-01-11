FROM docker.io/bitnami/magento:2

# Copy Magento init script
RUN mkdir -p /docker-entrypoint-init.d
COPY magento-init.sh /docker-entrypoint-init.d/magento-init.sh
RUN chmod +x /docker-entrypoint-init.d/magento-init.sh


# Fix Mutex posixsem for Mac M1
RUN echo 'Mutex posixsem' >> /opt/bitnami/apache/conf/httpd.conf