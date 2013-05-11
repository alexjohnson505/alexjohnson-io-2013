/*    Alex
 *    Iterating over a parameterized FMap
 *    FMap<K,V> is an immutable abstract data type 
 *    whose values represent finite functions from 
 *    keys of type K to values of type V.
 *    
 *    FMapIterator implements Iterator<K>, and
 *    iterates over the keys of a given FMap
 */

import java.util.*;

//Iterates over Keys in an FMap<K, V>
class FMapIterator<K, V> implements Iterator<K>{
	private FMap<K, V> f; //current value
	FMapIterator(FMap<K, V> f){
		this.f = f;
	}
	//Does this object have a next?
	public boolean hasNext(){
		return !(f.isEmpty());
	}
	//Updates m, stillGoing, and returns current Key
	public K next(){
		if(hasNext()){
			K current = this.f.fetchKey(); //Retrieves key of current 
			f = f.fetchNext(); //Retrieves next item an saves as current
			return current;
		} else {
			throw new NoSuchElementException("next doesn't exist");
		}
	}
	//Removes current object (operation not currently supported)
	public void remove(){
		throw new UnsupportedOperationException("Remove not supported");
	}

}
//FMap<K,V> is an immutable abstract data type whose values represent
//finite functions from keys of type K to values of type V.
public abstract class FMap<K, V> implements Iterable<K>{
	//Adds given FMap to this
	public abstract FMap<K, V> add(K k, V v);
	//Replaces value at given key
	abstract FMap<K, V> replaceValue(K k, V v);
	//is this FMap empty?
	public abstract boolean isEmpty();
	//returns size of this FMap
	public abstract int size();
	//Does this FMap contain given key?
	public abstract boolean containsKey(K k);
	//Get value from this FMap at given key
	public abstract V get(K k);
	//Does this FMap, and given FMap contain the same keys?
	abstract boolean sameKeys(FMap<K, V> m2);
	//Do the values of FMap, and given FMap correspond to the same values? 
	abstract boolean sameKeyValues(FMap<K, V> m2);
	//Are this FMap and given FMap equals? 
	abstract boolean equal(FMap<K, V> m2);
	//Retrieves key from this
	abstract K fetchKey();
	//Retrieves next key from this
	abstract FMap<K, V> fetchNext();
	//Converts this to an Array of K
	abstract ArrayList<K> convertToArray(ArrayList<K> arr);
	//returns new emptyMap
	static <K, V> FMap<K, V> emptyMap(){
		return new EmptyMap<K, V>();
	}
	//returns new emptyMap. Given comparator
	public static <K, V> FMap<K, V> emptyMap(Comparator<? super K> c){
		return new EmptyMap<K, V>();
	}
	//Are this FMap and given object equal?
	public boolean equals(Object x){
		//Make sure given object is an FMap
		if(x instanceof FMap){
			@SuppressWarnings(value="unchecked")
			FMap<K, V> m2 = (FMap<K, V>) x;
			return this.equal(m2);
		} else {
			return false;
		}
	}
	//Generate hashCode based on this
	public int hashCode(){
		return this.hashCode();
	}
	//Creates iterator for this
	public Iterator<K> iterator(){
		return new FMapIterator<K, V>(this);
	}
	//Creates iterator for this, and sorts with given comparator
	public Iterator<K> iterator(Comparator<? super K> c){
		return new FMapIterator<K, V>(this.sort(c));
	}
	//Sorts this by given comparator
	private FMap<K, V> sort(Comparator<? super K> c){
		ArrayList<K> arr = new ArrayList<K>(); //create new Array List
		this.convertToArray(arr); //Add keys of this to the Array
		Collections.sort(arr, c); //Use Collections.sort to sort Array
		//Converts back to sorted FMap<K,V> List and return
		return this.convertToFMap(arr); 
	}
	//Converts given ArrayList to an FMap
	private FMap<K, V> convertToFMap(ArrayList<K> arr){
		int size = arr.size();
		FMap<K, V> accumulator = FMap.emptyMap();
		//goes through each element of Array, and adds to accumulator
		//for(int i = 0; i < size; i++){
		for(int i = size - 1; i >= 0; i--){
			K key = arr.get(i);
			//Add takes a Key and a Value.
			//Adds Key, then uses Key to fetch Value
			accumulator = accumulator.add(key, this.get(key)); 
		}
		return accumulator; 
	}
}
//represents the empty FMap
class EmptyMap<K, V> extends FMap<K, V>{
	EmptyMap(){
	}
	//Adds given FMap to this
	FMap<K, V> add(K k, V v){
		return new Add<K, V>(k, v, this);
	}
	//Replaces value at given key
	FMap<K, V> replaceValue(K k, V v){
		return this;
	}
	//is this FMap empty?
	boolean isEmpty(){
		return true;
	}
	//returns size of this FMap
	int size(){
		return 0;
	}
	//Get value from this FMap at given key
	V get(K k){
		throw new RuntimeException("Error: Can't get(k) from an empty FMap");
	}
	//Does this FMap contain given key?
	boolean containsKey(K k) {
		return false;
	}
	//Return a string containing the size of this FMap
	public String toString(){
		return "{...(" + this.size() + " entries)...}";
	}
	//Are this FMap and given FMap equals?
	boolean equal(FMap<K, V> m2){
		return m2.isEmpty();
	}
	//Does this FMap, and given FMap contain the same keys?
	boolean sameKeys(FMap<K, V> m2){
		return true;
	}
	//Do the values of FMap, and given FMap correspond to the same values? 
	boolean sameKeyValues(FMap<K, V> m2){
		return true;
	}
	//Generate hashCode based on this
	public int hashCode(){
		return 0;
	}
	//Retrieves key from this
	K fetchKey(){
		throw new RuntimeException("No key in an empty");

	}
	//Retrieves next key from this
	FMap<K, V> fetchNext(){
		throw new RuntimeException("Can't get next from an empty");
	}
	//converts Keys of this to an Array 
	ArrayList<K> convertToArray(ArrayList<K> arr){
		return arr; //Returns array (end of recursion)
	}
}
//represents non-empty FMap
class Add<K, V> extends FMap<K, V>{
	K key;              //Represents the key for the stored value
	V value;            //Represents the stored value
	FMap<K, V> rest;    //Represents the rest of the list
	Add(K k, V v, FMap<K, V> rest) {
		this.key = k;
		this.value = v;
		this.rest = rest;
	}   
	//Adds given FMap to this
	FMap<K, V> add(K k, V v){
		if (this.containsKey(k)){
			//If duplicate key -> update old value
			return replaceValue(k, v); 
		} else {
			return new Add<K, V>(k, v, this);                   
		}
	}
	//Replaces value at given key
	FMap<K, V> replaceValue(K k, V v){
		//Find duplicate value
		if(this.key.equals(k)){
			return new Add<K, V>(k, v, this.rest);
		}else {
			//replace old value
			return new Add<K, V>(this.key, this.value, 
					this.rest.replaceValue(k, v));
		}        
	}
	//is this FMap empty?
	boolean isEmpty(){
		return false;
	}
	//returns size of this FMap
	int size(){
		if (this.rest.containsKey(key)){
			return this.rest.size();
		} else {
			//Only count unique keys for size
			return 1 + this.rest.size(); 
		}
	}
	//Does this FMap contain given key?
	boolean containsKey(K k){
		return this.key.equals(k) ||
				this.rest.containsKey(k);
	}
	//Get value from this FMap at given key
	V get(K k){
		if (this.key.equals(k)){
			return value;
		} else {
			return this.rest.get(k);
		}
	}
	//Return a string containing the size of this FMap
	public String toString(){
		return "{...(" + this.size() + " entries)...}";
	}
	//Is this FMap equal to given FMap?
	boolean equal(FMap<K, V> m2){
		//if different size - FMaps are not equal
		if (!(this.size() == m2.size())){
			return false;
		} else {
			//if same size -> check keys and values
			return this.sameKeys(m2) &&
					this.sameKeyValues(m2);
		}
	}
	//Does this FMap, and given FMap contain the same keys?
	boolean sameKeys(FMap<K, V> m2){
		return m2.containsKey(key)
				&& this.rest.sameKeys(m2);
	}
	//Do the values of FMap, and given FMap correspond to the same values? 
	boolean sameKeyValues(FMap<K, V> m2){
		return this.get(key).equals(m2.get(key)) &&
				this.rest.sameKeyValues(m2);

	}
	//Generate hashCode based on this
	public int hashCode(){
		//generate hashCode by adding hashcodes of all contained values.
		return this.value.hashCode() + this.rest.hashCode();
	}
	//Retrieves key from this
	K fetchKey(){
		return this.key;
	}
	//Retrieves next key from this
	FMap<K, V> fetchNext(){
		return this.rest;
	}
	//adds keys of this FMap<K,V> to an Array
	ArrayList<K> convertToArray(ArrayList<K> arr){
		arr.add(key);
		return this.rest.convertToArray(arr);
	}
}