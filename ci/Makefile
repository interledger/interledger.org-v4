.PHONY: info deploy backup restore list-backups deploy-staging deploy-production backup-production copy-production-to-staging promote-staging-to-production

info:
	@echo "Makefile for CI/CD tasks for interledger.org website"
	@echo ""
	@echo "Available tasks:"
	@echo "  deploy ENV=<environment> RUN=<run-identifier>       Deploy php files to the specified environment (staging or production)"
	@echo "  backup ENV=<environment> RUN=<run-identifier>       Backup the staging database and files"
	@echo "  restore ENV=<environment> FROM-RUN=<run-identifier> TARGETENV=<target-environment>    Restore the staging database and files from a specific backup"
	@echo "  list-backups ENV=<environment>                      List available backups for the specified environment"


deploy:
ifndef ENV
	$(error Error: ENV variable is not set. Usage: make deploy ENV=<environment> RUN=<run-identifier>)
endif
ifndef RUN
	$(eval RUN=$(shell hostname)-$(shell date +%Y%m%d))
endif
	@echo "Deploying to $(ENV) environment with run identifier: $(RUN)"
	$(eval TARGETUSER=deployer)	
	$(eval TARGETHOST=34.23.109.31)	
ifeq ($(ENV),staging)
	$(eval TARGETFOLDER=/var/www/staging)
	$(eval TARGETENV=staging)
else ifeq ($(ENV),production)
	$(eval TARGETFOLDER=/var/www/production)
	$(eval TARGETENV=production)
else
	$(error Error: ENV variable must be either 'staging' or 'production')
endif
	@echo "Syncing files to $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)..."
	rsync -avz --exclude=backupmanager --exclude='local' --exclude='web/sites/default/files' --exclude='vendor' --exclude='docker' --exclude='tmp' --exclude='e2e' --exclude='ci/' --exclude='.git/' --exclude='README.md' ../ $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/
	rsync ../ci/deploy/$(ENV)/settings.php $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/web/sites/default/settings.php
	rsync ../ci/deploy/$(ENV)/htaccess $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/web/.htaccess
	rsync ../ci/deploy/$(ENV)/robots.txt $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/web/robots.txt
	@echo "Running cleanup script on $(TARGETUSER)@$(TARGETHOST)..."
	scp ../ci/deploy/$(ENV)/cleanup.sh $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/cleanup.sh
	ssh $(TARGETUSER)@$(TARGETHOST) "cd $(TARGETFOLDER) && chmod +x cleanup.sh && ./cleanup.sh"
	@echo "Deployment to $(ENV) environment completed."

backup:
	@echo "Backing up $(ENV) database and files for run $(RUN)..."
	@echo "TODO"

restore:
	@echo "Restoring $(TARGETENV) database and files from backup of run $(FROM-RUN)..."
	@echo "TODO"

list-backups:
	@echo "Listing available backups for $(ENV)..."
	@echo "TODO"