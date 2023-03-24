<?php

return [
    // 注解扫描路径, 只扫描应用目录下已定义的文件夹，例如： app/admin/controller 及其下级目录
    'include_paths' => [
//        'admin',
//        'api',
        'third',
    ],
    'ignored' => [
        "after", "afterClass", "backupGlobals", "backupStaticAttributes", "before", "beforeClass", "codeCoverageIgnore*",
        "covers", "coversDefaultClass", "coversNothing", "dataProvider", "depends", "doesNotPerformAssertions",
        "expectedException", "expectedExceptionCode", "expectedExceptionMessage", "expectedExceptionMessageRegExp", "group",
        "large", "medium", "preserveGlobalState", "requires", "runTestsInSeparateProcesses", "runInSeparateProcess", "small",
        "test", "testdox", "testWith", "ticket", "uses" , "datetime",
    ]
];
