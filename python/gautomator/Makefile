DOMAIN=gautomator
PO_FILES=$(wildcard po/*.po)
DESKTOP_FILES=$(basename $(wildcard share/applications/*.desktop.in))
PYTHON=`which python`

all: clean po/$(DOMAIN).pot merge-po build-mo $(DESKTOP_FILES) build

# cleaning
clean:
	@rm -rf share/locale/
	@rm -f po/$(DOMAIN).pot
	@rm -f $(DESKTOP_FILES)
	@rm -rf build
	@find . -type f -name '*.pyc' -o -name '*.swp' -o -name '*~' | xargs rm -f
	@find share/gautomator/glade -type f \
		-name '*.bak' -o -name '*.gladep' | xargs rm -f

# update the pot
po/$(DOMAIN).pot:
	cd po; intltool-update -p -g $(DOMAIN)

# merge the new stuff into the po files
merge-po: $(PO_FILES) po/$(DOMAIN.pot)
	cd po; intltool-update -r -g $(DOMAIN)

# create mo from po files
%.mo : %.po
	mkdir -p share/locale/$</LC_MESSAGES/ 
	msgfmt $< -o share/locale/$</LC_MESSAGES/$(DOMAIN).mo 

# generate all *.mo files
build-mo: $(patsubst %.po,%.mo,$(PO_FILES))

# compile python files
build:
	$(PYTHON) setup.py build

# compile python files
install:
	$(PYTHON) setup.py install

# i18n for *.desktop files
%.desktop : %.desktop.in
	intltool-merge -d po/ $< $@
