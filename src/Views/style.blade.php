<style type="text/css">
	/**
	 * Base structure
	 */

	/* Move down content because we have a fixed navbar that is 50px tall */
	body {
	  padding-top: 50px;
	}


	/**
	 * Global add-ons
	 */

	.sub-header {
	  padding-bottom: 10px;
	  border-bottom: 1px solid #eee;
	}

	/**
	 * Top navigation
	 * Hide default border to remove 1px line.
	 */
	.navbar-fixed-top {
	  border: 0;
	}

	/**
	 * Sidebar
	 */

	/* Hide for mobile, show later */
	.sidebar {
	  display: none;
	}
	@media (min-width: 768px) {
	  .sidebar {
	    position: fixed;
	    top: 51px;
	    bottom: 0;
	    left: 0;
	    z-index: 1000;
	    display: block;
	    padding: 20px;
	    overflow-x: hidden;
	    overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
	    background-color: #f5f5f5;
	    border-right: 1px solid #eee;
	  }

	  .toc {
	    position: fixed;
	    top: 51px;
	    bottom: 0;
	    right: 0;
	    z-index: 1000;
	    display: block;
	    padding: 20px;
	    overflow-x: hidden;
	    overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
	    background-color: #f5f5f5;
	    border-right: 1px solid #eee;
	  }
	}


	/**
	 * Main content
	 */

	.main {
	  padding: 20px;
	}
	@media (min-width: 768px) {
	  .main {
	    padding-right: 40px;
	    padding-left: 40px;
	  }
	}
	.main .page-header {
	  margin-top: 0;
	}

	.search
	{
		padding-bottom:10px;
		text-align: center;
	}

	.main .panel-body h2
	{
		font-size:18px;
	}

	.view-source
	{
		cursor: pointer;
		float: right;
	}

	#sourceModal .modal-dialog
	{
		width: 80%;
	}

	.toc .list-group-item
	{
		cursor: pointer;
	}

</style>