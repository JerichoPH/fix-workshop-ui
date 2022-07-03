#!bin/bash
time=$(date "+%Y-%m-%d")
mysqldump --extended-insert -u root -p'JW087073yjz' maintain | gzip > "./$time.sql.gz"
php ../artisan sqlBackup:toMaintainGroup "$time.sql.gz"
