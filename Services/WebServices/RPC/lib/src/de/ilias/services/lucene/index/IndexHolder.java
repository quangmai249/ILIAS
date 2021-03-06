/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

package de.ilias.services.lucene.index;

import de.ilias.services.lucene.settings.LuceneSettings;
import java.io.File;
import java.io.IOException;
import java.util.HashMap;
import java.util.logging.Level;

import org.apache.logging.log4j.LogManager;
import org.apache.lucene.analysis.standard.StandardAnalyzer;
import org.apache.lucene.index.CorruptIndexException;
import org.apache.lucene.index.IndexWriter;
import de.ilias.services.settings.ClientSettings;
import de.ilias.services.settings.ConfigurationException;
import de.ilias.services.settings.LocalSettings;
import de.ilias.services.settings.ServerSettings;
import org.apache.logging.log4j.Logger;
import org.apache.lucene.index.IndexWriterConfig;

/**
 * Capsulates the interaction between IndexReader and IndexWriter
 * This class is a singleton for each index path.
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 */
public class IndexHolder {
	
	protected static Logger logger = LogManager.getLogger(IndexHolder.class);
	
	public static final int MAX_NUM_SEGMENTS = 100;
	
	private static HashMap<String, IndexHolder> instances = new HashMap<String, IndexHolder>();
	private ClientSettings settings;
	private IndexWriter writer;
	
	

	/**
	 * @param indexPath
	 * @param indexType
	 * @throws IOException 
	 */
	private IndexHolder(String clientKey) throws IOException {

		try {
			settings = ClientSettings.getInstance(clientKey);
		}
		catch (ConfigurationException e) {
			throw new IOException("Caught configuration exception: " + e.getMessage());
		}

	}

	/**
	 * 
	 * @param clientKey
	 * @return
	 * @throws IOException
	 */
	public static synchronized IndexHolder getInstance(String clientKey) throws 
		IOException { 
		
		String hash = clientKey;
		
		if(instances.containsKey(hash)) {
			return instances.get(hash);
		}
		instances.put(hash,new IndexHolder(clientKey));
		return instances.get(hash);
	}
	
	/**
	 * 
	 * @param indexType
	 * @return
	 * @throws IOException
	 */
	public static synchronized IndexHolder getInstance() throws IOException  {
		
		return getInstance(LocalSettings.getClientKey());
	}
	
	public static void deleteIndex() throws ConfigurationException
	{
		File indexPath = ClientSettings.getInstance(LocalSettings.getClientKey()).getIndexPath();

		deleteTree(indexPath);
		logger.info("Deleted index directory: " + indexPath.getAbsoluteFile());
	}
	
	/**
	 * Delete directory recursive
	 * @param path
	 * @return
	 */
	private static boolean deleteTree(File path) {
		
		if(!path.exists() || !path.isDirectory())
		{
			return false;
		}
		for(File del : path.listFiles()) {
			
			if(del.isDirectory()) {
				deleteTree(del);
			}
			else {
				del.delete();
			}
		}
		path.delete();
		return true;
	}

	/**
	 * Close all writers
	 */
	public static synchronized void closeAllWriters() {
		
		logger.info("Closing document writers...");
		
		for(Object key : instances.keySet()) {
			try {
				logger.info("Closing writer: " + (String) key);
				IndexHolder holder = instances.get((String) key);
				IndexDirectoryFactory.getDirectory(ClientSettings.getInstance((String) key).getIndexPath()).close();
				holder.close();
			}
			catch (ConfigurationException | IOException ex)
			{
				logger.error("Cannot close fs directory: " + ex.getMessage());
			}

		}
		
		logger.info("Index writers closed.");
	}
	
	/**
	 * @todo obtain lock for index writer
	 * @throws IOException
	 */
	public void init() throws IOException, ConfigurationException {
		
		try {
			logger.debug("Adding new separated index for " + LocalSettings.getClientKey());
			
			IndexWriterConfig writerConfig = new IndexWriterConfig(
				new StandardAnalyzer()
			);
			writerConfig.
				setOpenMode(IndexWriterConfig.OpenMode.CREATE_OR_APPEND).
				setRAMBufferSizeMB(ServerSettings.getInstance().getRAMSize());
			writer = new IndexWriter(
				IndexDirectoryFactory.getDirectory(settings.getIndexPath()),
				writerConfig
			);
		}
		catch(IOException | ConfigurationException e) {
			throw e;
		}
		
	}
	
	/**
	 * @return the writer
	 */
	public IndexWriter getWriter() {
		return writer;
	}

	/**
	 * @param writer the writer to set
	 */
	public void setWriter(IndexWriter writer) {
		this.writer = writer;
	}

	/**
	 * Close writer 
	 */
	public void close() {
		
		try {
			getWriter().close();
			IndexDirectoryFactory.getDirectory(settings.getIndexPath()).close();

		} catch (CorruptIndexException e) {
			logger.fatal("Index corrupted." + e);
		} catch (IOException e) {
			logger.fatal("Error closing writer." + e);
		}
	}

	/* (non-Javadoc)
	 * @see java.lang.Object#finalize()
	 */
	@Override
	protected void finalize() throws Throwable {
		
		try {
			close();
		}
		finally {
			super.finalize();
		}
	}
}
