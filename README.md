# webservco/component

A PHP component/library.

---

Project skeleton / project started.

## Usage

- [Create project repository](https://github.com/organizations/webservco/repositories/new);

### Customize

```shell
git clone git@github.com:webservco/component.git __COMPONENT__
cd {component}
git remote set-url origin git@github.com:webservco/__COMPONENT__.git
rm -f src/WebServCo/.gitignore && git add src/WebServCo && git commit -m 'Init src'
printf '%s\n' '# webservco/__COMPONENT__' '' 'A PHP component/library.' '' '---' > README.md
#ag --json -l -Q 'webservco/component' . | xargs sed -i -e 's|"name" : "webservco/component"|"name" : "webservco/__COMPONENT__"|g'
sed -i -e 's|"name" : "webservco/component"|"name" : "webservco/__COMPONENT__"|g' composer.json
git add README.md && git add composer.json && git commit -m 'Customize' && git push -u origin main
```

---

## Index

- webservco/command
- webservco/component (project template)
- webservco/configuration
- webservco/configuration-legacy
- webservco/controller
- webservco/database
- webservco/database-legacy
- webservco/data-transfer
- webservco/log
- webservco/view
