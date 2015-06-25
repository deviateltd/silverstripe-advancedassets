<?xml version="1.0" encoding="utf-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <clear/>
                <rule name="Advanced Assets Clean URL" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="false" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="/$frameworkDir/main.php?url={PATH_INFO}" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
        <httpErrors errorMode="Detailed">
        </httpErrors>
    </system.webServer>
</configuration>