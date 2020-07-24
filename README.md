#NextCloudWebDavSdk#
Обертка на классы WebDav и Share

$sdk = new \NextCloudWebDavSdk\NextCloudWebDavSdk(
    'http://192.168.0.1',
    'login',
    'pass'
);
$response = $sdk->webDav->getListingFolder();
$response = $sdk->share->createShare();

Все методы классов расписаны ниже.

##WebDav##

Документация по работе с WebDav
https://docs.nextcloud.com/server/latest/developer_manual/client_apis/WebDAV/index.html

###Авторизация###
$webDav = new \NextCloudWebDavSdk\WebDav\Server(
    'host',
    'login',
    'pass'
);

###Получить список файлов###
$response = $webDav->getListingFolder(
    '/path/to/file'
);

###Скачать файл###
$response = $webDav->downloadFile(
    '/path/to/download/file',
    'path/to/save/file'
);


###Загрузка файлов###
$response = $webDav->uploadFile(
    'path/to/upload/file/test_upload_pdf.pdf',
    '/path/for/save/file/'
);
    
    
    

###Создать папку###
$response = $webDav->createFolder(
    '/path/to/new/folder/',
);

###Удалить файл или директорию###
$response = $webDav->removeFileOrDirectory(
    '/path/to/file/or/directory',
);

###Переместить файл или дирректорию###
$response = $webDav->moveFileOrDirectory(
    '/path/file/to/move',
    '/path/file/to/destination'
);

###Копировать файл или директорию###
$response = $webDav->copyFileOrDirectory(
    '/path/file/to/copy',
    '/path/file/to/destination'
);

###Поиск файлов и папок
Выбираемые свойства искомых элементов  
```$selectProperties = ['{DAV:}getlastmodified','{DAV:}getetag', ... ];```

Область поиска относительно корневой папки пользователя  
```$searchScope = '/path/to/destination/';```

Уровень вложенности поиска  
```$searchScopeDepth = 'infinity';```

Условия поиска  
```$searchWhere = ['eq::{http://owncloud.org/ns}fileid' => 999, ...]```

Сортировка  
```$searchOrderBy = ['{http://owncloud.org/ns}fileid' => 'ascending', ...]```

```$response = $webDav->search($selectProperties, $searchScope, $searchScopeDepth, $searchWhere, $searchOrderBy);```

##Share##

Документация по шарингу 
https://docs.nextcloud.com/server/latest/developer_manual/client_apis/OCS/ocs-share-api.html

###Авторизация###
$share = new \NextCloudWebDavSdk\OCS\Share(
    'host',
    'login',
    'pass'
);

###Расшарить файл###
$response = $share->createShare(
    'path/to/share/file'
);

###Удалить шару###
$response = $share->removeShare(
    'shareID'
);


###Получить шару###
$response = $share->getShares(
     'path/to/share/file'
);

###Обновить шару###
$response = $share->updateShare(
    'shareID'
);

