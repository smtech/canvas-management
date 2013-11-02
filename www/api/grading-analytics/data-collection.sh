# 0 0 * * 6 <path to canvas>/api/grading-analytics/data-collection.sh

BASEDIR=$(dirname $0)
cd $BASEDIR
php ./data-collection.php