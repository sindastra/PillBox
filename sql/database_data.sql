/**
 * PillBox
 * Copyright (C) 2016 Sindastra <sindastra@gmail.com>
 *
 * The above copyright notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Please note that this is just some example data and may not be accurate.
 */

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'Demo','284c8faef72b3b4ca4ed00e363a6e5d1e37ef7855ffa761ee6612e7e29d12a28239d2c327d0351c51e231ba16876e8f2bfcaabb0a3469513e13026fff98f560f','demo@example.com','90150cbc871824c5c704c63f43bbaef32beddcb85fa59fdbc835e0461c7096782a20324a8f5e969ae043030833926c54fc76dcc0e4964034bc5189c3e8dd6bc2','2016-02-21 05:42:31');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `medications` WRITE;
/*!40000 ALTER TABLE `medications` DISABLE KEYS */;
INSERT INTO `medications` VALUES (1,1,'Paracetamol','',500,'mg',2,'Tablet',0,0,0,'Take when having pain.',4,'Hours',4000,'mg per day',1,1,'Only take when having pain.','2016-02-20 17:36:01'),(2,1,'Estrofem','estradiol',2,'mg',4,'mg',0,0,0,'-',6,'hours',4,'pills',0,NULL,'Don\'t take all at once!','2016-02-21 08:57:31'),(3,1,'Ibuprofen','',400,'mg',1,'Tablet',0,0,0,'Take when having pain.',3,'Hours',3,'Pills per day',1,1,'Only take when having pain.','2016-02-20 17:36:01')
/*!40000 ALTER TABLE `medications` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `measurement_log` WRITE;
/*!40000 ALTER TABLE `measurement_log` DISABLE KEYS */;
INSERT INTO `measurement_log` VALUES (1,1,60,'2016-02-21 16:02:53','2016-02-21 16:02:51'),(2,1,58,'2016-02-21 21:01:31','2016-02-21 21:01:29'),(3,2,165,'2016-02-21 21:07:01','2016-02-21 21:07:00'),(4,2,165,'2016-02-21 22:02:04','2016-02-21 22:02:02');
/*!40000 ALTER TABLE `measurement_log` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `measurements` WRITE;
/*!40000 ALTER TABLE `measurements` DISABLE KEYS */;
INSERT INTO `measurements` VALUES (1,1,'Weight','Kg','2016-02-21 15:57:34'),(2,1,'Height','cm','2016-02-21 21:06:25');
/*!40000 ALTER TABLE `measurements` ENABLE KEYS */;
UNLOCK TABLES;
