#############
composer 설치 방법
#############
# mac : 
brew install composer

# linux
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

sudo mv composer.phar /usr/local/bin/composer
composer --version


##############
# install
##############

cd app
composer clear-cache
composer dump-autoload -o
composer install

cd ..
docker-compose down
docker-compose --env-file .env up -d --build
docker-compose logs -f


##############
# 접속 테스트
##############
curl localhost:8888


##############
# BUILD
##############

# build up
docker-compose --env-file .env up -d --build flexreact-php
docker-compose logs -f

# 도커 컨테이너 내부 접근
docker exec -it flexreact-php /bin/sh

# 도커 실시간 로그 확인
docker-compose logs -f

##############
# db 접속
##############
docker-compose exec flexreact-php-mysql mysql -u test -p test_db
docker-compose exec flexreact-php-mysql mysql -u root -p mysql

##############
# workbench
##############
127.0.0.1
root

##############
# 데이터 백업 및 복원
##############

# 데이터 백업
1. mysqldump
docker exec flexreact-php-mysql sh -c 'exec mysqldump --all-databases -uroot -p"$MYSQL_ROOT_PASSWORD"' > $(pwd)/flexreact-php-mysql-data/backup.sql
docker exec flexreact-php-mysql sh -c 'exec mysqldump test_db -uroot -p"$MYSQL_ROOT_PASSWORD"' > $(pwd)/flexreact-php-mysql-data/test_db_backup.sql

2. tar 파일로 백업
docker run --rm --volumes-from flexreact-php-mysql -v $(pwd):/backup ubuntu tar cvf /backup/mysql_backup.tar /var/lib/mysql


# 데이터 복원
1. mysqldump
docker exec -i flexreact-php-mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < $(pwd)/flexreact-php-mysql-data/backup.sql

2. tar 파일 
docker run --rm --volumes-from flexreact-php-mysql -v $(pwd):/backup ubuntu bash -c "cd /var/lib/mysql && tar xvf /backup/mysql_backup.tar --strip 1"


##############
## .env
##############
# DB 접속 정보 # flexreact-php-mysql
