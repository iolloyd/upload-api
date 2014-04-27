System Configuration
====================

Server
------

To automatically select the SSH key, add the following lines to `~/.ssh/config`:

```
Host *.ec2.dev.cldsys.net
    IdentityFile=~/.ssh/alex-cldsys-dev.pem
```

Connect as `ubuntu`:

```
$ ssh ubuntu@cloudxxx-dev.ec2.dev.cldsys.net
```

Filesystem
----------

```
/
    etc/
        nginx/
            sites-enabled/
                cloudxxx
                cloudxxx-ng
    srv/
        cloudxxx/
        cloudxxx-ng/
```
