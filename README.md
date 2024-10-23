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
docker-compose logs -f --tail 10


##############
# 접속 테스트
##############
curl localhost:8888


##############
# BUILD
##############

# build up
docker-compose --env-file .env up -d --build flexreactphp
docker-compose logs -f --tail 10

# 도커 컨테이너 내부 접근
docker exec -it flexreactphp /bin/sh

# 도커 실시간 로그 확인
docker-compose logs -f --tail 10

##############
# db 접속
##############
docker-compose exec flexreactphp-mysql mysql -u test -p test_db
docker-compose exec flexreactphp-mysql mysql -u root -p mysql

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
docker exec flexreactphp-mysql sh -c 'exec mysqldump --all-databases -uroot -p"$MYSQL_ROOT_PASSWORD"' > $(pwd)/flexreactphp-mysql-data/backup.sql
docker exec flexreactphp-mysql sh -c 'exec mysqldump test_db -uroot -p"$MYSQL_ROOT_PASSWORD"' > $(pwd)/flexreactphp-mysql-data/test_db_backup.sql

2. tar 파일로 백업
docker run --rm --volumes-from flexreactphp-mysql -v $(pwd):/backup ubuntu tar cvf /backup/mysql_backup.tar /var/lib/mysql


# 데이터 복원
1. mysqldump
docker exec -i flexreactphp-mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < $(pwd)/flexreactphp-mysql-data/backup.sql

2. tar 파일 
docker run --rm --volumes-from flexreactphp-mysql -v $(pwd):/backup ubuntu bash -c "cd /var/lib/mysql && tar xvf /backup/mysql_backup.tar --strip 1"


##############
## .env
##############
# DB 접속 정보 # flexreactphp-mysql
